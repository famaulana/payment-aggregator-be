<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApiLoginRequest;
use App\Services\AuthService;
use App\Enums\ResponseCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Passport\Token;

class ApiAuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function login(ApiLoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->loginWithApiKey(
                apiKey: $request->api_key,
                apiSecret: $request->api_secret
            );

            return $this->success(
                data: [
                    'client' => $result['client'],
                    'access_token' => $result['token']['access_token'],
                    'token_type' => $result['token']['token_type'],
                    'expires_in' => $result['token']['expires_in'],
                    'expires_at' => $result['token']['expires_at'],
                ],
                message: __('auth.api_login_success')
            );
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return $this->error(
                code: ResponseCode::AUTHENTICATION_FAILED,
                message: $e->getMessage()
            );
        } catch (\Exception $e) {
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('auth.api_login_error')
            );
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $token = $request->user()->token();

            $this->authService->logout($token);

            return $this->success(
                message: __('auth.api_logout_success')
            );
        } catch (\Exception $e) {
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('auth.api_logout_error')
            );
        }
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        $client = $user->client;

        return $this->success(
            data: [
                'client' => [
                    'id' => $client->id,
                    'name' => $client->client_name,
                    'code' => $client->client_code,
                ],
                'rate_limit' => $this->getRateLimitInfo($user),
            ],
            message: __('auth.api_profile_retrieved')
        );
    }

    private function getRateLimitInfo($user): array
    {
        $apiKey = \App\Models\ApiKey::where('client_id', $user->client_id)
            ->where('status', 'active')
            ->first();

        if (!$apiKey) {
            return [];
        }

        return [
            'per_minute' => $apiKey->rate_limit_per_minute,
            'per_hour' => $apiKey->rate_limit_per_hour,
            'total_requests' => $apiKey->total_requests,
            'last_used_at' => $apiKey->last_used_at?->toDateTimeString(),
        ];
    }
}
