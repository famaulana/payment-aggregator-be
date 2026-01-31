<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\ApiRequestLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LogApiRequest
{
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        try {
            $processingTime = defined('LARAVEL_START') 
                ? (int) ((microtime(true) - LARAVEL_START) * 1000)
                : 0;

            $user = Auth::user();
            $apiKey = null;
            $clientId = null;

            // Try to get client_id from authenticated user or from request
            if ($user) {
                $clientId = $user->client_id;
                $apiKey = ApiKey::where('client_id', $clientId)
                    ->where('status', \App\Enums\ApiKeyStatus::ACTIVE)
                    ->first();
            } else {
                // For login endpoint, try to get client from api_key in request
                $inputApiKey = $request->input('api_key');
                if ($inputApiKey) {
                    $keyRecord = ApiKey::where('api_key', $inputApiKey)->first();
                    if ($keyRecord) {
                        $clientId = $keyRecord->client_id;
                        $apiKey = $keyRecord;
                    }
                }
            }

            $requestBody = null;
            $inputData = $request->input();
            if (!empty($inputData)) {
                $requestBody = $this->sanitizeData($inputData);
            }

            $responseBody = null;
            $content = $response->getContent();
            if (!empty($content)) {
                $responseData = json_decode($content, true);
                if ($responseData && is_array($responseData)) {
                    $responseBody = $this->sanitizeData($responseData);
                }
            }

            // Only log if we have a client_id
            if ($clientId) {
                ApiRequestLog::create([
                    'api_key_id' => $apiKey?->id,
                    'client_id' => $clientId,
                    'endpoint' => $request->fullUrl(),
                    'method' => $request->method(),
                    'request_headers' => $request->headers->all(),
                    'request_body' => $requestBody,
                    'response_status' => $response->getStatusCode(),
                    'response_body' => $responseBody,
                    'processing_time_ms' => $processingTime,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ]);
            }
        } catch (\Exception $e) {
            if (config('app.debug')) {
                logger()->error('Failed to log API request: ' . $e->getMessage());
            }
        }
    }

    private function sanitizeData(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $sensitiveKeys = [
            'password', 'api_secret', 'api_key', 'secret', 'token', 
            'access_token', 'refresh_token', 'authorization',
            'credit_card', 'cvv', 'pin', 'otp'
        ];

        $sanitized = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $sanitized[$key] = '[MASKED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeData($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
