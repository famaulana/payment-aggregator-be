<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Enums\ApiKeyStatus;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class RateLimitByApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        // Get API key from header
        $apiKeyValue = $request->header('X-API-Key');

        if (!$apiKeyValue) {
            // If no API key, skip rate limiting
            return $next($request);
        }

        // Get the API key record from the request if it's already been processed by other middleware
        $apiKeyRecord = $request->get('api_key_record');

        if (!$apiKeyRecord) {
            // If not already loaded, fetch from database
            $apiKeyRecord = ApiKey::where('api_key', $apiKeyValue)
                ->where('status', ApiKeyStatus::ACTIVE)
                ->first();
        }

        if (!$apiKeyRecord) {
            // If invalid API key, skip rate limiting (will be caught by other middleware)
            return $next($request);
        }

        // Check rate limits
        $minuteLimit = $apiKeyRecord->rate_limit_per_minute;
        $hourLimit = $apiKeyRecord->rate_limit_per_hour;

        // Check minute-based rate limit
        if ($minuteLimit && $minuteLimit > 0) {
            $keyMinute = 'api_key_minute_' . $apiKeyRecord->id;
            if (!$this->attemptRateLimit($keyMinute, $minuteLimit, 60)) { // 60 seconds = 1 minute
                return response()->json([
                    'response_code' => \App\Enums\ResponseCode::TOO_MANY_REQUESTS->value,
                    'response_message' => __('messages.too_many_requests'),
                ], \App\Enums\ResponseCode::TOO_MANY_REQUESTS->getHttpStatusCode());
            }
        }

        // Check hour-based rate limit
        if ($hourLimit && $hourLimit > 0) {
            $keyHour = 'api_key_hour_' . $apiKeyRecord->id;
            if (!$this->attemptRateLimit($keyHour, $hourLimit, 3600)) { // 3600 seconds = 1 hour
                return response()->json([
                    'response_code' => \App\Enums\ResponseCode::TOO_MANY_REQUESTS->value,
                    'response_message' => __('messages.too_many_requests'),
                ], \App\Enums\ResponseCode::TOO_MANY_REQUESTS->getHttpStatusCode());
            }
        }

        return $next($request);
    }

    /**
     * Attempt to increment the rate limit and check if it's exceeded
     */
    private function attemptRateLimit(string $key, int $limit, int $ttl): bool
    {
        $current = Redis::incr($key);

        if ($current == 1) {
            // Set expiration only on first increment
            Redis::expire($key, $ttl);
        }

        return $current <= $limit;
    }
}