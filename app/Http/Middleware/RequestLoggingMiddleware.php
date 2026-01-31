<?php

namespace App\Http\Middleware;

use App\Models\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestLoggingMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $duration = microtime(true) - $startTime;

        if ($this->shouldLogRequest($request)) {
            $this->logRequest($request, $response, $duration);
        }

        return $response;
    }

    protected function shouldLogRequest(Request $request): bool
    {
        $excludedPaths = [
            'health',
            'up',
            'api/health',
        ];

        foreach ($excludedPaths as $path) {
            if (str_contains($request->path(), $path)) {
                return false;
            }
        }

        return true;
    }

    protected function logRequest(Request $request, Response $response, float $duration): void
    {
        try {
            $apiKeyRecord = $request->input('api_key_record');
            $userId = auth()->check() ? auth()->id() : null;

            ApiRequestLog::create([
                'api_key_id' => $apiKeyRecord?->id,
                'user_id' => $userId,
                'method' => $request->method(),
                'endpoint' => $this->getEndpoint($request),
                'request_headers' => $this->sanitizeHeaders($request->headers->all()),
                'request_body' => $this->sanitizeBody($request->all()),
                'response_code' => $response->getStatusCode(),
                'response_body' => $this->sanitizeBody(json_decode($response->getContent(), true)),
                'client_ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'request_duration' => round($duration * 1000, 2),
                'response_size' => strlen($response->getContent()),
            ]);
        } catch (\Exception $e) {
            if (config('app.debug')) {
                logger()->error('Failed to log API request', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }
    }

    protected function getEndpoint(Request $request): string
    {
        return '/' . ltrim(str_replace(config('app.url'), '', $request->url()), '/');
    }

    protected function sanitizeHeaders(array $headers): array
    {
        $sensitiveKeys = [
            'authorization',
            'x-api-key',
            'x-api-secret',
            'x-signature',
            'cookie',
        ];

        foreach ($sensitiveKeys as $key) {
            if (isset($headers[$key])) {
                $headers[$key] = ['***REDACTED***'];
            }
        }

        return $headers;
    }

    protected function sanitizeBody(array $body): ?array
    {
        if (empty($body)) {
            return null;
        }

        $sensitiveKeys = [
            'password',
            'api_key',
            'api_secret',
            'secret',
            'token',
            'credit_card_number',
            'cvv',
        ];

        foreach ($sensitiveKeys as $key) {
            if (isset($body[$key])) {
                $body[$key] = '***REDACTED***';
            }
        }

        return $body;
    }
}
