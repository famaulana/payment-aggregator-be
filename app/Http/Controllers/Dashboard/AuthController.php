<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RefreshTokenRequest;
use App\Services\Dashboard\DashboardAuthService;
use App\Enums\ResponseCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private DashboardAuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                email: $request->email,
                password: $request->password
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
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('auth.refresh_error')
            );
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $token = $request->user()->token();
            $this->authService->logout($token);

            return $this->success(message: __('auth.logout_success'));
        } catch (\Exception $e) {
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('auth.logout_error')
            );
        }
    }

    public function logoutAll(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $this->authService->logoutAllTokens($user);

            return $this->success(message: __('auth.logout_all_success'));
        } catch (\Exception $e) {
            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: __('auth.logout_all_error')
            );
        }
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $entity = $user->entity;
        $entityType = $this->getUserEntityTypeFromEntity($user);

        $data = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'role' => $user->role_name,
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
            'entity_type' => $entityType,
            'last_login_at' => $user->last_login_at?->toDateTimeString(),
        ];

        if ($entityType === 'client' && $entity instanceof \App\Models\Client) {
            $data['client'] = [
                'id' => $entity->id,
                'code' => $entity->client_code,
                'name' => $entity->client_name,
            ];
        } elseif ($entityType === 'head_office' && $entity instanceof \App\Models\HeadOffice) {
            $data['head_office'] = [
                'id' => $entity->id,
                'code' => $entity->code,
                'name' => $entity->name,
            ];
            $data['client'] = [
                'id' => $entity->client->id,
                'code' => $entity->client->client_code,
                'name' => $entity->client->client_name,
            ];
        } elseif ($entityType === 'merchant' && $entity instanceof \App\Models\Merchant) {
            $data['merchant'] = [
                'id' => $entity->id,
                'code' => $entity->merchant_code,
                'name' => $entity->merchant_name,
            ];
            $data['head_office'] = [
                'id' => $entity->headOffice->id,
                'code' => $entity->headOffice->code,
                'name' => $entity->headOffice->name,
            ];
            $data['client'] = [
                'id' => $entity->client->id,
                'code' => $entity->client->client_code,
                'name' => $entity->client->client_name,
            ];
        } elseif ($entityType === 'system_owner' && $entity instanceof \App\Models\SystemOwner) {
            $data['system_owner'] = [
                'id' => $entity->id,
                'name' => $entity->name ?? $entity->pic_name,
                'email' => $entity->email ?? $entity->pic_email,
            ];
        }

        return $this->success(data: $data, message: __('auth.profile_retrieved'));
    }

    private function getUserEntityTypeFromEntity($user): ?string
    {
        $entity = $user->entity;

        if ($entity instanceof \App\Models\SystemOwner) {
            return 'system_owner';
        } elseif ($entity instanceof \App\Models\Client) {
            return 'client';
        } elseif ($entity instanceof \App\Models\HeadOffice) {
            return 'head_office';
        } elseif ($entity instanceof \App\Models\Merchant) {
            return 'merchant';
        }

        return null;
    }
}
