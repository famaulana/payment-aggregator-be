<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateUserRequest;
use App\Http\Requests\CreateUserWithEntityRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\Dashboard\UserManagementService;
use App\Enums\ResponseCode;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function __construct(
        private UserManagementService $userService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = [
            'role' => $request->role,
            'status' => $request->status,
            'entity_type' => $request->entity_type,
            'entity_id' => $request->entity_id,
            'search' => $request->search,
        ];

        $users = $this->userService->getUsers($filters);

        $users = $users->setCollection($users->getCollection()->map(function ($item) {
            return new UserResource($item);
        }));

        return $this->pagination(
            paginator: $users,
            message: __('messages.users_retrieved')
        );
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->createUser(
                username: $request->username,
                email: $request->email,
                password: $request->password,
                fullName: $request->full_name,
                role: $request->role,
                entityType: $request->entity_type,
                entityId: $request->entity_id,
                status: $request->status ?? 'active'
            );

            return $this->created(
                data: new UserResource($user),
                message: __('messages.user_created')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.resource_not_found'));
        } catch (\Exception $e) {
            Log::error('User creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: $e->getMessage()
            );
        }
    }

    public function storeWithEntity(CreateUserWithEntityRequest $request): JsonResponse
    {
        try {
            $entityType = $request->entity_type;

            $userData = [
                'username' => $request->username,
                'email' => $request->email,
                'password' => $request->password,
                'full_name' => $request->full_name,
                'role' => $request->role,
                'status' => $request->status ?? 'active',
            ];

            $entityData = $this->extractEntityData($request, $entityType);

            $user = $this->userService->createUserWithEntity($userData, $entityData, $entityType);

            return $this->created(
                data: new UserResource($user),
                message: __('messages.user_and_entity_created')
            );
        } catch (\Exception $e) {
            Log::error('User with entity creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: $e->getMessage()
            );
        }
    }

    private function extractEntityData($request, string $entityType): array
    {
        if ($entityType === 'client') {
            return [
                'client_code' => $request->client_code,
                'client_name' => $request->client_name,
                'business_type' => $request->business_type,
                'bank_name' => $request->bank_name,
                'bank_account_number' => $request->bank_account_number,
                'bank_account_holder_name' => $request->bank_account_holder_name,
                'bank_branch' => $request->bank_branch,
                'pic_name' => $request->pic_name,
                'pic_position' => $request->pic_position,
                'pic_phone' => $request->pic_phone,
                'pic_email' => $request->pic_email,
                'company_phone' => $request->company_phone,
                'company_email' => $request->company_email,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'address' => $request->address,
                'postal_code' => $request->postal_code,
            ];
        } elseif ($entityType === 'head_office') {
            return [
                'code' => $request->head_office_code,
                'name' => $request->head_office_name,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'sub_district_id' => $request->sub_district_id,
                'address' => $request->address,
                'postal_code' => $request->postal_code,
                'phone' => $request->phone,
                'email' => $request->ho_email,
            ];
        } elseif ($entityType === 'merchant') {
            return [
                'merchant_code' => $request->merchant_code,
                'merchant_name' => $request->merchant_name,
                'head_office_id' => $request->head_office_id,
                'province_id' => $request->province_id,
                'city_id' => $request->city_id,
                'district_id' => $request->district_id,
                'sub_district_id' => $request->sub_district_id,
                'address' => $request->address,
                'postal_code' => $request->postal_code,
                'phone' => $request->phone,
                'email' => $request->merchant_email,
                'pos_merchant_id' => $request->pos_merchant_id,
            ];
        }

        return [];
    }

    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->getUserById($id);

            return $this->success(
                data: new UserResource($user),
                message: __('messages.user_retrieved')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.user_not_found'));
        } catch (\Exception $e) {
            return $this->forbidden($e->getMessage());
        }
    }

    public function update(int $id, UpdateUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->updateUser(
                id: $id,
                username: $request->username,
                email: $request->email,
                fullName: $request->full_name,
                role: $request->role,
                status: $request->status
            );

            return $this->updated(
                data: new UserResource($user),
                message: __('messages.user_updated')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.user_not_found'));
        } catch (\Exception $e) {
            Log::error('User update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->error(
                code: ResponseCode::INTERNAL_SERVER_ERROR,
                message: $e->getMessage()
            );
        }
    }

    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $user = $this->userService->toggleUserStatus($id);

            return $this->success(
                data: new UserResource($user),
                message: __('messages.user_status_updated')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.user_not_found'));
        } catch (\Exception $e) {
            return $this->forbidden($e->getMessage());
        }
    }

    public function resetPassword(int $id, Request $request): JsonResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        try {
            $user = $this->userService->resetUserPassword($id, $request->password);

            return $this->success(
                data: new UserResource($user),
                message: __('messages.user_password_reset')
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->notFound(__('messages.user_not_found'));
        } catch (\Exception $e) {
            return $this->forbidden($e->getMessage());
        }
    }
}
