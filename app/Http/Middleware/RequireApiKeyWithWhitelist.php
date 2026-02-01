<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Enums\ApiKeyStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireApiKeyWithWhitelist
{
    public function handle(Request $request, Closure $next): Response
    {
        // Skip validation for login endpoint
        if ($request->is('api/v1/login')) {
            return $next($request);
        }

        // Get API key from header
        $apiKeyValue = $request->header('X-API-Key');

        if (!$apiKeyValue) {
            return response()->json([
                'response_code' => \App\Enums\ResponseCode::API_KEY_REQUIRED->value,
                'response_message' => __('messages.api_key_required'),
            ], \App\Enums\ResponseCode::API_KEY_REQUIRED->getHttpStatusCode());
        }

        // Find the API key in the database
        $apiKey = ApiKey::where('api_key', $apiKeyValue)
            ->where('status', ApiKeyStatus::ACTIVE)
            ->first();

        if (!$apiKey) {
            return response()->json([
                'response_code' => \App\Enums\ResponseCode::INVALID_API_KEY->value,
                'response_message' => __('messages.invalid_api_key'),
            ], \App\Enums\ResponseCode::INVALID_API_KEY->getHttpStatusCode());
        }


        // Add the API key record to the request for later use
        $request->merge(['api_key_record' => $apiKey]);

        // Increment request counter
        $apiKey->increment('total_requests');
        $apiKey->update(['last_used_at' => now()]);

        return $next($request);
    }
}
