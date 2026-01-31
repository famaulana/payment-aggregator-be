<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCode;
use App\Services\Shared\ResponseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\Response;

class SignatureValidationMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldValidateSignature($request)) {
            $validationResult = $this->validateSignature($request);

            if ($validationResult !== true) {
                return $validationResult;
            }
        }

        return $next($request);
    }

    protected function shouldValidateSignature(Request $request): bool
    {
        $excludedPaths = Config::get('signature.excluded_paths', [
            'api/v1/login',
            'api/v1/register',
            'health',
            'up',
        ]);

        foreach ($excludedPaths as $path) {
            if (str_contains($request->path(), $path)) {
                return false;
            }
        }

        return $request->hasHeader('X-Signature') && $request->hasHeader('X-Timestamp');
    }

    protected function validateSignature(Request $request): Response|bool
    {
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');
        $apiKey = $request->header('X-API-Key');

        if (!$timestamp || !is_numeric($timestamp)) {
            return ResponseService::error(
                ResponseCode::INVALID_INPUT,
                __('messages.invalid_timestamp')
            );
        }

        $requestTime = now()->createFromTimestamp($timestamp);
        $now = now();
        $diff = abs($now->diffInSeconds($requestTime));

        $allowedTolerance = Config::get('signature.time_tolerance', 300);

        if ($diff > $allowedTolerance) {
            return ResponseService::error(
                ResponseCode::REQUEST_EXPIRED,
                __('messages.request_expired')
            );
        }

        $apiKeyRecord = $request->input('api_key_record');

        if (!$apiKeyRecord) {
            return ResponseService::error(
                ResponseCode::UNAUTHORIZED,
                __('messages.invalid_api_key')
            );
        }

        $expectedSignature = $this->generateSignature($request, $apiKeyRecord, $timestamp);

        if (!hash_equals($expectedSignature, $signature)) {
            return ResponseService::error(
                ResponseCode::INVALID_SIGNATURE,
                __('messages.invalid_signature')
            );
        }

        return true;
    }

    protected function generateSignature(Request $request, $apiKeyRecord, string $timestamp): string
    {
        $method = strtoupper($request->method());
        $url = $this->getCanonicalUrl($request);
        $payload = $this->getPayload($request);

        $dataToSign = $method . "\n" . $url . "\n" . $timestamp . "\n" . $payload;

        return hash_hmac('sha256', $dataToSign, $apiKeyRecord->api_secret_hashed);
    }

    protected function getCanonicalUrl(Request $request): string
    {
        return '/' . ltrim(parse_url($request->fullUrl(), PHP_URL_PATH), '/');
    }

    protected function getPayload(Request $request): string
    {
        if ($request->isMethod('GET') || $request->isMethod('HEAD')) {
            $query = $request->query->all();
            unset($query['X-Signature'], $query['X-Timestamp']);
            ksort($query);
            return http_build_query($query);
        }

        $body = $request->all();
        unset($body['X-Signature'], $body['X-Timestamp']);
        ksort($body);
        return json_encode($body, JSON_UNESCAPED_SLASHES);
    }
}
