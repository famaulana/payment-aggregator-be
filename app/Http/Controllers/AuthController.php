<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Services\AuthService;
use App\Enums\ResponseCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Passport\Token;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                email: $request->email,
                password: $request->password,
                clientType: 'dashboard'
            );

            return $this->success(
                data: [
                    'user' => $result['user'],
                    'access_token' => $result['token']['access_token'],
                    'refresh_token' => $result['token']['refresh_token'],
                    'token_type' => $result['token']['token_type'],
                    'expires_in' => $result['token']['expires_in'],
                    'expires_at' => $result['token']['expires_at'],
                ],
                message: __('auth.login_success')
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
                message: __('auth.login_error')
            );
        }
    }

    public function refresh(RefreshTokenRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->refreshToken(
                refreshToken: $request->refresh_token
            );

            return $this->success(
                data: [
                    'access_token' => $result['token']['access_token'],
                    'refresh_token' => $result['token']['refresh_token'],
                    'token_type' => $result['token']['token_type'],
                    'expires_in' => $result['token']['expires_in'],
                    'expires_at' => $result['token']['expires_at'],
                ],
                message: __('auth.refresh_success')
            );
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return $this->error(
                code: ResponseCode::TOKEN_INVALID,
                message: $e->getMessage()
            );
        } catch (\Exception $e) {
            return $this->error(
                code: ResponseCode::ERR_SERVER_ERROR,
                message: __('auth.refresh_error')
            );
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $token = $request->user()->token();

            $this->authService->logout($token);

            return $this->success(
                message: __('auth.logout_success')
            );
        } catch (\Exception $e) {
            return $this->error(
                code: ResponseCode::ERR_SERVER_ERROR,
                message: __('auth.logout_error')
            );
        }
    }

    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $this->authService->logoutAllTokens($user);

            return $this->success(
                message: __('auth.logout_all_success')
            );
        } catch (\Exception $e) {
            return $this->error(
                code: ResponseCode::ERR_SERVER_ERROR,
                message: __('auth.logout_all_error')
            );
        }
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->success(
            data: [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'role' => $user->role_name,
                'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
                'entity_type' => $this->getUserEntityType($user),
                'client_id' => $user->client_id,
                'head_office_id' => $user->head_office_id,
                'merchant_id' => $user->merchant_id,
                'last_login_at' => $user->last_login_at?->toDateTimeString(),
            ],
            message: __('auth.profile_retrieved')
        );
    }

    public function tokens(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $tokens = $this->authService->getUserTokens($user);

            return $this->success(
                data: [
                    'tokens' => $tokens,
                ],
                message: __('auth.tokens_retrieved')
            );
        } catch (\Exception $e) {
            return $this->error(
                code: ResponseCode::ERR_SERVER_ERROR,
                message: __('auth.tokens_error')
            );
        }
    }

    private function getUserEntityType($user): ?string
    {
        if ($user->client_id && !$user->head_office_id && !$user->merchant_id) {
            return 'client';
        }
        if ($user->head_office_id && !$user->merchant_id) {
            return 'head_office';
        }
        if ($user->merchant_id) {
            return 'merchant';
        }

        return null;
    }
}
