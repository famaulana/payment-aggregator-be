<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Client;
use App\Models\HeadQuarter;
use App\Models\Merchant;
use App\Models\SystemOwner;
use App\Services\Shared\AuditTrailService;
use App\Exceptions\AppException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserManagementService
{
    private const SYSTEM_OWNER_ROLES = [
        'system_owner',
        'system_owner_admin',
        'system_owner_finance',
        'system_owner_support',
    ];

    private const CLIENT_ROLES = [
        'client',
        'client_admin',
        'client_finance',
        'client_operations',
    ];

    private const HEAD_QUARTER_ROLES = [
        'head_quarter',
    ];

    private const MERCHANT_ROLES = [
        'merchant',
    ];

    public function __construct(
        private AuditTrailService $auditService
    ) {}

    public function getUsers(array $filters = []): LengthAwarePaginator
    {
        $currentUser = auth()->user();
        $query = User::with(['entity', 'creator', 'roles']);

        if ($currentUser->isSystemOwner()) {
            $query->whereHas('roles', function ($q) {
                $q->whereNotIn('name', self::SYSTEM_OWNER_ROLES);
            });
        } elseif ($currentUser->isClientUser()) {
            $clientId = $currentUser->getClientId();

            // Get IDs for head quarters and merchants belonging to this client
            $headQuarterIds = HeadQuarter::where('client_id', $clientId)->pluck('id');
            $merchantIds = Merchant::where('client_id', $clientId)->pluck('id');

            $query->where(function ($q) use ($clientId, $headQuarterIds, $merchantIds) {
                // Users directly under the client
                $q->where(function ($subQuery) use ($clientId) {
                    $subQuery->where('entity_type', 'client')
                        ->where('entity_id', $clientId);
                })
                    // Users under head quarters owned by this client
                    ->orWhere(function ($subQuery) use ($headQuarterIds) {
                        $subQuery->where('entity_type', 'head_quarter')
                            ->whereIn('entity_id', $headQuarterIds);
                    })
                    // Users under merchants owned by this client
                    ->orWhere(function ($subQuery) use ($merchantIds) {
                        $subQuery->where('entity_type', 'merchant')
                            ->whereIn('entity_id', $merchantIds);
                    });
            });
        } elseif ($currentUser->isHeadQuarterUser()) {
            $headQuarterId = $currentUser->getHeadQuarterId();

            // Get IDs for merchants belonging to this head quarter
            $merchantIds = Merchant::where('head_quarter_id', $headQuarterId)->pluck('id');

            $query->where(function ($q) use ($headQuarterId, $merchantIds) {
                // Users directly under this head quarter
                $q->where(function ($subQuery) use ($headQuarterId) {
                    $subQuery->where('entity_type', 'head_quarter')
                        ->where('entity_id', $headQuarterId);
                })
                    // Users under merchants owned by this head quarter
                    ->orWhere(function ($subQuery) use ($merchantIds) {
                        $subQuery->where('entity_type', 'merchant')
                            ->whereIn('entity_id', $merchantIds);
                    });
            });
        } else {
            // Merchant or other roles cannot access user management
            throw new \Illuminate\Auth\AuthenticationException(__('messages.unauthorized_action'));
        }

        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['entity_type'])) {
            $query->where('entity_type', $this->resolveEntityClass($filters['entity_type']));
        }

        if (!empty($filters['entity_id'])) {
            $query->where('entity_id', $filters['entity_id']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%");
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate(15);
    }

    public function createUser(
        string $username,
        string $email,
        string $password,
        string $fullName,
        string $role,
        string $entityType,
        int $entityId,
        string $status = 'active'
    ): User {
        $currentUser = auth()->user();

        $this->validateUserCreation($currentUser, $role, $entityType, $entityId);

        $entityClass = $this->resolveEntityClass($entityType);
        $entity = $this->getEntity($entityClass, $entityId);

        DB::beginTransaction();
        try {
            $user = User::create([
                'username' => $username,
                'email' => $email,
                'password' => Hash::make($password),
                'full_name' => $fullName,
                'entity_type' => $entityClass,
                'entity_id' => $entityId,
                'status' => $status,
                'created_by' => $currentUser->id,
            ]);

            $user->assignSingleRole($role);

            $this->auditService->logUserCreate($user->id, [
                'username' => $username,
                'email' => $email,
                'full_name' => $fullName,
                'role' => $role,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ]);

            DB::commit();

            return $user->load(['entity', 'creator', 'roles']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function createUserWithEntity(array $userData, array $entityData, string $entityType): User
    {
        $currentUser = auth()->user();

        DB::beginTransaction();
        try {
            $entity = null;
            $entityClass = $this->resolveEntityClass($entityType);

            if ($entityType === 'system_owner') {
                $entity = SystemOwner::create(array_merge($entityData, [
                    'status' => 'active',
                ]));
            } elseif ($entityType === 'client') {
                $entity = Client::create(array_merge($entityData, [
                    'system_owner_id' => $currentUser->entity_id,
                    'created_by' => $currentUser->id,
                    'kyb_status' => 'not_required',
                    'status' => 'active',
                ]));
            } elseif ($entityType === 'head_quarter') {
                $entity = HeadQuarter::create(array_merge($entityData, [
                    'client_id' => $currentUser->getClientId(),
                    'status' => 'active',
                ]));
            } elseif ($entityType === 'merchant') {
                $clientId = $currentUser->getClientId();
                $merchantData = array_merge($entityData, [
                    'client_id' => $clientId,
                    'created_by' => $currentUser->id,
                    'status' => 'active',
                ]);

                // Auto-detect and set head_quarter_id if creator is a headquarter user
                if ($currentUser->isHeadQuarterUser()) {
                    $headQuarterId = $currentUser->getHeadQuarterId();
                    if ($headQuarterId) {
                        $merchantData['head_quarter_id'] = $headQuarterId;
                    }
                }

                $entity = Merchant::create($merchantData);
            }

            if (!$entity) {
                throw AppException::serverError(__('messages.failed_to_create_entity'));
            }

            $user = User::create([
                'username' => $userData['username'],
                'email' => $userData['email'],
                'password' => Hash::make($userData['password']),
                'full_name' => $userData['full_name'],
                'entity_type' => $entityClass,
                'entity_id' => $entity->id,
                'status' => $userData['status'] ?? 'active',
                'created_by' => $currentUser->id,
            ]);

            $user->assignSingleRole($userData['role']);

            $this->auditService->logUserCreate($user->id, [
                'username' => $userData['username'],
                'email' => $userData['email'],
                'full_name' => $userData['full_name'],
                'role' => $userData['role'],
                'entity_type' => $entityType,
                'entity_id' => $entity->id,
                'entity_created' => true,
            ]);

            DB::commit();

            return $user->load(['entity', 'creator', 'roles']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function getUserById(int $id): User
    {
        $currentUser = auth()->user();
        $user = User::with(['entity', 'creator', 'roles'])->findOrFail($id);

        if (!$this->canAccessUser($currentUser, $user)) {
            throw AppException::forbidden();
        }

        return $user;
    }

    public function updateUser(
        int $id,
        ?string $username = null,
        ?string $email = null,
        ?string $fullName = null,
        ?string $role = null,
        ?string $status = null,
        array $rawEntityData = []
    ): User {
        $currentUser = auth()->user();
        $user = User::with(['entity', 'creator', 'roles'])->findOrFail($id);

        if (!$this->canAccessUser($currentUser, $user)) {
            throw AppException::forbidden();
        }

        if ($role && in_array($role, self::SYSTEM_OWNER_ROLES)) {
            throw AppException::forbidden(__('messages.cannot_assign_system_owner_role'));
        }

        $oldValues = [
            'username' => $user->username,
            'email' => $user->email,
            'full_name' => $user->full_name,
            'role' => $user->role_name,
            'status' => $user->status,
        ];

        if ($username) $user->username = $username;
        if ($email) $user->email = $email;
        if ($fullName) $user->full_name = $fullName;
        if ($status) $user->status = $status;

        // Build entity data based on current user's role (not target user's role)
        $entityData = $this->buildEntityDataForUpdate($currentUser, $user, $rawEntityData);

        DB::beginTransaction();
        try {
            $user->save();

            if ($role && $role !== $user->role_name) {
                $user->assignSingleRole($role);
            }

            if (!empty($entityData) && $user->entity) {
                $user->entity->update($entityData);
            }

            $this->auditService->logUserUpdate($user->id, $oldValues, [
                'username' => $user->username,
                'email' => $user->email,
                'full_name' => $user->full_name,
                'role' => $user->role_name,
                'status' => $user->status,
            ]);

            DB::commit();

            return $user->fresh(['entity', 'creator', 'roles']);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function toggleUserStatus(int $id): User
    {
        $currentUser = auth()->user();
        $user = User::with(['entity', 'creator', 'roles'])->findOrFail($id);

        if (!$this->canAccessUser($currentUser, $user)) {
            throw AppException::forbidden();
        }

        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        $this->auditService->logUserStatusToggle($user->id, $user->status);

        return $user->fresh(['entity', 'creator', 'roles']);
    }

    public function resetUserPassword(int $id, string $newPassword): User
    {
        $currentUser = auth()->user();
        $user = User::with(['entity', 'creator', 'roles'])->findOrFail($id);

        if (!$this->canAccessUser($currentUser, $user)) {
            throw AppException::forbidden();
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        $this->auditService->logUserPasswordReset($user->id);

        return $user->fresh(['entity', 'creator', 'roles']);
    }

    public function changeUserPassword(int $userId, string $oldPassword, string $newPassword): User
    {
        $currentUser = auth()->user();

        // Ensure user can only change their own password
        if ($currentUser->id !== $userId) {
            throw AppException::forbidden(__('messages.cannot_change_other_user_password'));
        }

        $user = User::with(['entity', 'creator', 'roles'])->findOrFail($userId);

        // Verify old password matches current password
        if (!Hash::check($oldPassword, $user->password)) {
            throw AppException::forbidden(__('messages.old_password_incorrect'));
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        $this->auditService->logUserPasswordReset($user->id);

        return $user->fresh(['entity', 'creator', 'roles']);
    }

    private function validateUserCreation(User $currentUser, string $role, string $entityType, int $entityId): void
    {
        // Only the top-level system_owner role can create other system_owner sub-roles.
        // system_owner_admin and below cannot create system_owner users.
        if (in_array($role, self::SYSTEM_OWNER_ROLES)) {
            if (!$currentUser->hasRole('system_owner')) {
                throw AppException::forbidden(__('messages.cannot_create_system_owner'));
            }
            // system_owner can create sub-roles but not another system_owner
            if ($role === 'system_owner') {
                throw AppException::forbidden(__('messages.cannot_create_system_owner'));
            }
            if ($entityType !== 'system_owner') {
                throw AppException::invalidInput(__('messages.entity_type_must_be_system_owner'));
            }
            return;
        }

        if ($currentUser->isSystemOwner()) {
            if (!in_array($role, self::CLIENT_ROLES)) {
                throw AppException::invalidInput(__('messages.system_owner_can_only_create_client_users'));
            }
            if ($entityType !== 'client') {
                throw AppException::invalidInput(__('messages.entity_type_must_be_client'));
            }
        } elseif ($currentUser->isClientUser()) {
            if (!in_array($role, array_merge(self::HEAD_QUARTER_ROLES, self::MERCHANT_ROLES))) {
                throw AppException::invalidInput(__('messages.client_can_only_create_head_quarter_or_merchant_users'));
            }

            $clientId = $currentUser->getClientId();
            $entityClass = $this->resolveEntityClass($entityType);
            $entity = $this->getEntity($entityClass, $entityId);

            if ($entityType === 'head_quarter' && $entity->client_id !== $clientId) {
                throw AppException::forbidden(__('messages.head_quarter_must_belong_to_your_client'));
            }

            if ($entityType === 'merchant' && $entity->client_id !== $clientId) {
                throw AppException::forbidden(__('messages.merchant_must_belong_to_your_client'));
            }
        } elseif ($currentUser->isHeadQuarterUser()) {
            if (!in_array($role, self::MERCHANT_ROLES)) {
                throw AppException::invalidInput(__('messages.head_quarter_can_only_create_merchant_users'));
            }

            if ($entityType !== 'merchant') {
                throw AppException::invalidInput(__('messages.entity_type_must_be_merchant'));
            }

            $headQuarterId = $currentUser->getHeadQuarterId();
            $entity = Merchant::findOrFail($entityId);

            if ($entity->head_quarter_id !== $headQuarterId) {
                throw AppException::forbidden(__('messages.merchant_must_belong_to_your_head_quarter'));
            }
        }
    }

    private function canAccessUser(User $currentUser, User $targetUser): bool
    {
        // Any user can always access/update themselves
        if ($currentUser->id === $targetUser->id) {
            return true;
        }

        \Log::info('canAccessUser check', [
            'current_user_id' => $currentUser->id,
            'current_user_entity_type' => $currentUser->entity_type,
            'current_user_role' => $currentUser->role_name,
            'current_user_is_system_owner' => $currentUser->isSystemOwner(),
            'current_user_is_client' => $currentUser->isClientUser(),
            'current_user_is_head_quarter' => $currentUser->isHeadQuarterUser(),
            'current_user_client_id' => $currentUser->getClientId(),
            'current_user_head_quarter_id' => $currentUser->getHeadQuarterId(),
            'target_user_id' => $targetUser->id,
            'target_user_entity_type' => $targetUser->entity_type,
            'target_user_role' => $targetUser->role_name,
            'target_user_is_system_owner' => $targetUser->isSystemOwner(),
            'target_user_client_id' => $targetUser->getClientId(),
            'target_user_head_quarter_id' => $targetUser->getHeadQuarterId(),
        ]);

        if ($currentUser->isSystemOwner()) {
            return !$targetUser->isSystemOwner();
        }

        if ($currentUser->isClientUser()) {
            $clientId = $currentUser->getClientId();
            return $targetUser->getClientId() === $clientId;
        }

        if ($currentUser->isHeadQuarterUser()) {
            $headQuarterId = $currentUser->getHeadQuarterId();
            return $targetUser->getHeadQuarterId() === $headQuarterId;
        }

        return false;
    }

    /**
     * Build entity data for update based on target user's entity type.
     * This method handles field aliases (e.g. ho_email → email, merchant_email → email).
     * Note: Input should already be filtered to contain only entity fields by the request layer.
     */
    private function buildEntityDataForUpdate(User $currentUser, User $targetUser, array $input): array
    {
        if (empty($input)) {
            return [];
        }

        // Handle field aliases based on target user's entity type
        if ($targetUser->entity_type === HeadQuarter::class || $targetUser->entity_type === 'head_quarter') {
            // ho_email → entity email column
            if (array_key_exists('ho_email', $input)) {
                $input['email'] = $input['ho_email'];
                unset($input['ho_email']);
            }
        }

        if ($targetUser->entity_type === Merchant::class || $targetUser->entity_type === 'merchant') {
            // merchant_email → entity email column
            if (array_key_exists('merchant_email', $input)) {
                $input['email'] = $input['merchant_email'];
                unset($input['merchant_email']);
            }
        }

        return $input;
    }

    /**
     * Pick only keys that exist in $input (preserves explicit nulls).
     */
    private function pickFields(array $input, array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $input)) {
                $result[$key] = $input[$key];
            }
        }
        return $result;
    }

    private function resolveEntityClass(string $entityType): string
    {
        return match ($entityType) {
            'system_owner' => SystemOwner::class,
            'client'       => Client::class,
            'head_quarter' => HeadQuarter::class,
            'merchant'     => Merchant::class,
            default => throw AppException::invalidInput(__('messages.invalid_entity_type')),
        };
    }

    private function getEntity(string $entityClass, int $entityId)
    {
        return $entityClass::findOrFail($entityId);
    }
}
