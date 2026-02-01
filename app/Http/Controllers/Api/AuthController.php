<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ApiLoginRequest;
use App\Services\Api\ApiAuthService;
use App\Enums\ResponseCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private ApiAuthService $authService
    ) {}

    public function login(ApiLoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
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
            Log::error($e);
            return $this->error(
                code: ResponseCode::AUTHENTICATION_FAILED,
                message: $e->getMessage()
            );
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('auth.api_login_error')
            );
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            if (!$user) {
                return $this->error(
                    code: ResponseCode::UNAUTHENTICATED,
                    message: __('auth.unauthenticated')
                );
            }

            $token = $user->token();
            $this->authService->logout($token);

            return $this->success(message: __('auth.api_logout_success'));
        } catch (\Exception $e) {
            Log::error($e);
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('auth.api_logout_error')
            );
        }
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return $this->error(
                code: ResponseCode::UNAUTHENTICATED,
                message: __('auth.unauthenticated')
            );
        }

        $client = $user->entity;

        if (!$client || !($client instanceof \App\Models\Client)) {
            return $this->error(code: ResponseCode::NOT_FOUND, message: __('auth.client_not_found'));
        }

        $apiKey = \App\Models\ApiKey::where('client_id', $client->id)
            ->where('status', \App\Enums\ApiKeyStatus::ACTIVE)
            ->first();

        $data = [
            'client' => [
                'id' => $client->id,
                'code' => $client->client_code,
                'name' => $client->client_name,
                'business_type' => $client->business_type,
                'kyb_status' => $client->kyb_status->value,
            ],
        ];

        if ($apiKey) {
            $data['rate_limit'] = [
                'per_minute' => $apiKey->rate_limit_per_minute,
                'per_hour' => $apiKey->rate_limit_per_hour,
                'total_requests' => $apiKey->total_requests,
                'last_used_at' => $apiKey->last_used_at?->toDateTimeString(),
            ];
        }

        return $this->success(data: $data, message: __('auth.api_profile_retrieved'));
    }
}
