<?php

namespace App\Http\Middleware;

use App\Enums\ResponseCode;
use App\Models\ApiKey;
use App\Services\ResponseService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class ApiKeyMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $apiKey = $request->header('X-API-Key');
        $apiSecret = $request->header('X-API-Secret');
        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');

        if (!$apiKey) {
            return ResponseService::error(
                ResponseCode::UNAUTHORIZED,
                __('messages.api_key_required')
            );
        }

        $keyRecord = ApiKey::where('api_key', $apiKey)
            ->where('status', 'active')
            ->first();

        if (!$keyRecord) {
            return ResponseService::error(
                ResponseCode::INVALID_API_KEY,
                __('messages.invalid_api_key')
            );
        }

        if ($keyRecord->status->isInactive() || $keyRecord->status->isRevoked()) {
            return ResponseService::error(
                ResponseCode::API_KEY_REVOKED,
                __('messages.api_key_revoked')
            );
        }

        if (!empty($keyRecord->ip_whitelist)) {
            $requestIp = $request->ip();
            if (!in_array($requestIp, $keyRecord->ip_whitelist) && $requestIp !== '127.0.0.1') {
                return ResponseService::error(
                    ResponseCode::FORBIDDEN,
                    __('messages.ip_not_allowed')
                );
            }
        }

        if ($apiSecret && !Hash::check($apiSecret, $keyRecord->api_secret_hashed)) {
            return ResponseService::error(
                ResponseCode::UNAUTHORIZED,
                __('messages.invalid_api_secret')
            );
        }

        if ($signature && $timestamp) {
            $this->validateSignature($request, $keyRecord, $signature, $timestamp);
        }

        $keyRecord->increment('total_requests');
        $keyRecord->update(['last_used_at' => now()]);

        $request->merge(['api_key_record' => $keyRecord]);

        return $next($request);
    }

    protected function validateSignature(
        Request $request,
        ApiKey $keyRecord,
        string $signature,
        string $timestamp
    ): void {
        $requestTime = now()->createFromTimestamp($timestamp);
        $now = now();
        $diff = $now->diffInMinutes($requestTime);

        if (abs($diff) > 5) {
            abort(
                ResponseService::error(
                    ResponseCode::UNAUTHORIZED,
                    __('messages.request_expired')
                )->getStatusCode(),
                json_encode(ResponseService::error(
                    ResponseCode::UNAUTHORIZED,
                    __('messages.request_expired')
                )->getData(true))
            );
        }

        $payload = $request->except(['X-Signature']);
        ksort($payload);
        $queryString = http_build_query($payload);

        $expectedSignature = hash_hmac(
            'sha256',
            $request->method() . "\n" . $request->url() . "\n" . $timestamp . "\n" . $queryString,
            $keyRecord->api_secret_hashed
        );

        if (!hash_equals($expectedSignature, $signature)) {
            abort(
                ResponseService::error(
                    ResponseCode::INVALID_SIGNATURE,
                    __('messages.invalid_signature')
                )->getStatusCode(),
                json_encode(ResponseService::error(
                    ResponseCode::INVALID_SIGNATURE,
                    __('messages.invalid_signature')
                )->getData(true))
            );
        }
    }
}
