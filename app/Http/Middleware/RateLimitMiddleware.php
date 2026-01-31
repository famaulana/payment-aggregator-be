<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCode;
use App\Models\ApiKey;
use App\Services\ResponseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class RateLimitMiddleware
{
    protected int $maxAttempts;
    protected int $decayMinutes;
    protected bool $useRedis;

    public function __construct()
    {
        $this->maxAttempts = config('app.rate_limit.max_attempts', 60);
        $this->decayMinutes = config('app.rate_limit.decay_minutes', 1);
        $this->useRedis = config('cache.default') === 'redis' && env('REDIS_HOST');
    }

    public function handle(Request $request, Closure $next, $maxAttempts = null, $decayMinutes = null): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($maxAttempts) {
            $this->maxAttempts = (int) $maxAttempts;
        }

        if ($decayMinutes) {
            $this->decayMinutes = (int) $decayMinutes;
        }

        if ($this->limiter()->tooManyAttempts($key, $this->maxAttempts)) {
            return $this->buildResponse($key);
        }

        $this->limiter()->hit($key, $this->decayMinutes * 60);

        $response = $next($request);

        $response->headers->set(
            'X-RateLimit-Limit',
            $this->maxAttempts,
            false
        );
        $response->headers->set(
            'X-RateLimit-Remaining',
            max($this->maxAttempts - $this->limiter()->attempts($key) + 1, 0),
            false
        );
        $response->headers->set(
            'X-RateLimit-Reset',
            $this->limiter()->availableIn($key),
            false
        );

        return $response;
    }

    protected function resolveRequestSignature(Request $request): string
    {
        $apiKey = $request->input('api_key_record')?->id ?? $request->header('X-API-Key');

        if ($apiKey) {
            return sha1($apiKey . '|' . $request->ip() . '|' . $request->route()->getName());
        }

        return sha1($request->ip() . '|' . $request->route()->getName());
    }

    protected function limiter()
    {
        return app('cache')->driver(
            $this->useRedis ? 'redis' : 'file'
        )->store('rate_limiter');
    }

    protected function buildResponse(string $key): Response
    {
        $retryAfter = $this->limiter()->availableIn($key);

        return ResponseService::error(
            ResponseCode::TOO_MANY_REQUESTS,
            __('messages.too_many_requests'),
            [
                'retry_after' => $retryAfter,
                'limit' => $this->maxAttempts,
            ]
        )->setStatusCode(429);
    }
}
