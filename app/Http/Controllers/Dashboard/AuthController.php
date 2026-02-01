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
            Log::error($e);
            return $this->error(
                code: ResponseCode::TOKEN_INVALID,
                message: $e->getMessage()
            );
        } catch (\Exception $e) {
            Log::error($e);
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
            Log::error($e);
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
            Log::error($e);
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

        $data['entity'] = $this->formatEntityData($entity, $entityType);

        return $this->success(data: $data, message: __('auth.profile_retrieved'));
    }

    private function formatEntityData($entity, string $entityType): array
    {
        $baseData = [
            'id'   => $entity->id,
            'type' => $entityType,
            'name' => $this->getEntityName($entity, $entityType),
            'code' => $this->getEntityCode($entity, $entityType),
        ];

        switch ($entityType) {

            case 'system_owner':
                $baseData += [
                    'business_type' => $entity->business_type,
                    'pic_name'      => $entity->pic_name,
                    'pic_position'  => $entity->pic_position,
                    'pic_phone'     => $entity->pic_phone,
                    'pic_email'     => $entity->pic_email,
                    'company_phone' => $entity->company_phone,
                    'company_email' => $entity->company_email,
                    'address'       => $entity->address,
                    'postal_code'   => $entity->postal_code,
                ];
                break;

            case 'client':
                $baseData += [
                    'business_type' => $entity->business_type,
                    'kyb_status'    => $entity->kyb_status?->value,
                    'pic_name'      => $entity->pic_name,
                    'pic_position'  => $entity->pic_position,
                    'pic_phone'     => $entity->pic_phone,
                    'pic_email'     => $entity->pic_email,
                    'company_phone' => $entity->company_phone,
                    'company_email' => $entity->company_email,
                    'address'       => $entity->address,
                    'postal_code'   => $entity->postal_code,
                ];

                // Parent: System Owner
                if ($entity->systemOwner) {
                    $baseData['parent_entities']['system_owner'] = [
                        'id'   => $entity->systemOwner->id,
                        'type' => 'system_owner',
                        'name' => $entity->systemOwner->name,
                        'code' => $entity->systemOwner->code,
                    ];
                }
                break;

            case 'head_office':
                $baseData += [
                    'phone'       => $entity->phone,
                    'email'       => $entity->email,
                    'address'     => $entity->address,
                    'postal_code' => $entity->postal_code,
                ];

                // Parent: Client
                if ($entity->client) {
                    $baseData['parent_entities']['client'] = [
                        'id'   => $entity->client->id,
                        'type' => 'client',
                        'name' => $entity->client->client_name,
                        'code' => $entity->client->client_code,
                    ];
                }
                break;

            case 'merchant':
                $baseData += [
                    'phone'       => $entity->phone,
                    'email'       => $entity->email,
                    'address'     => $entity->address,
                    'postal_code' => $entity->postal_code,
                ];

                // Parent: Head Office
                if ($entity->headOffice) {
                    $baseData['parent_entities']['head_office'] = [
                        'id'   => $entity->headOffice->id,
                        'type' => 'head_office',
                        'name' => $entity->headOffice->name,
                        'code' => $entity->headOffice->code,
                    ];
                }

                // Parent: Client
                if ($entity->client) {
                    $baseData['parent_entities']['client'] = [
                        'id'   => $entity->client->id,
                        'type' => 'client',
                        'name' => $entity->client->client_name,
                        'code' => $entity->client->client_code,
                    ];
                }
                break;
        }

        return $baseData;
    }

    private function getEntityName($entity, string $entityType): ?string
    {
        switch ($entityType) {
            case 'system_owner':
                return $entity->name;
            case 'client':
                return $entity->client_name;
            case 'head_office':
                return $entity->name;
            case 'merchant':
                return $entity->merchant_name;
            default:
                return null;
        }
    }

    private function getEntityCode($entity, string $entityType): ?string
    {
        switch ($entityType) {
            case 'system_owner':
                return $entity->code;
            case 'client':
                return $entity->client_code;
            case 'head_office':
                return $entity->code;
            case 'merchant':
                return $entity->merchant_code;
            default:
                return null;
        }
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
