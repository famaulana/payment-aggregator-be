<?php

namespace App\Services\Dashboard;

use App\Models\User;
use App\Models\Client;
use App\Models\HeadQuarter;
use App\Models\Merchant;
use App\Services\Shared\AuditTrailService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

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
                    $subQuery->where('entity_type', Client::class)
                        ->where('entity_id', $clientId);
                })
                // Users under head quarters owned by this client
                ->orWhere(function ($subQuery) use ($headQuarterIds) {
                    $subQuery->where('entity_type', HeadQuarter::class)
                        ->whereIn('entity_id', $headQuarterIds);
                })
                // Users under merchants owned by this client
                ->orWhere(function ($subQuery) use ($merchantIds) {
                    $subQuery->where('entity_type', Merchant::class)
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
                    $subQuery->where('entity_type', HeadQuarter::class)
                        ->where('entity_id', $headQuarterId);
                })
                // Users under merchants owned by this head quarter
                ->orWhere(function ($subQuery) use ($merchantIds) {
                    $subQuery->where('entity_type', Merchant::class)
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

            if ($entityType === 'client') {
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
                $entity = Merchant::create(array_merge($entityData, [
                    'client_id' => $clientId,
                    'created_by' => $currentUser->id,
                    'status' => 'active',
                ]));
            }

            if (!$entity) {
                throw new \Exception(__('messages.failed_to_create_entity'));
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
            throw new \Exception(__('messages.forbidden'));
        }

        return $user;
    }

    public function updateUser(
        int $id,
        ?string $username = null,
        ?string $email = null,
        ?string $fullName = null,
        ?string $role = null,
        ?string $status = null
    ): User {
        $currentUser = auth()->user();
        $user = User::with(['entity', 'creator', 'roles'])->findOrFail($id);

        if (!$this->canAccessUser($currentUser, $user)) {
            throw new \Exception(__('messages.forbidden'));
        }

        if ($role && in_array($role, self::SYSTEM_OWNER_ROLES)) {
            throw new \Exception(__('messages.cannot_assign_system_owner_role'));
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

        DB::beginTransaction();
        try {
            $user->save();

            if ($role && $role !== $user->role_name) {
                $user->assignSingleRole($role);
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
            throw new \Exception(__('messages.forbidden'));
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
            throw new \Exception(__('messages.forbidden'));
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        $this->auditService->logUserPasswordReset($user->id);

        return $user->fresh(['entity', 'creator', 'roles']);
    }

    private function validateUserCreation(User $currentUser, string $role, string $entityType, int $entityId): void
    {
        if (in_array($role, self::SYSTEM_OWNER_ROLES)) {
            throw new \Exception(__('messages.cannot_create_system_owner'));
        }

        if ($currentUser->isSystemOwner()) {
            if (!in_array($role, self::CLIENT_ROLES)) {
                throw new \Exception(__('messages.system_owner_can_only_create_client_users'));
            }
            if ($entityType !== 'client') {
                throw new \Exception(__('messages.entity_type_must_be_client'));
            }
        } elseif ($currentUser->isClientUser()) {
            if (!in_array($role, array_merge(self::HEAD_QUARTER_ROLES, self::MERCHANT_ROLES))) {
                throw new \Exception(__('messages.client_can_only_create_head_quarter_or_merchant_users'));
            }

            $clientId = $currentUser->getClientId();
            $entityClass = $this->resolveEntityClass($entityType);
            $entity = $this->getEntity($entityClass, $entityId);

            if ($entityType === 'head_quarter' && $entity->client_id !== $clientId) {
                throw new \Exception(__('messages.head_quarter_must_belong_to_your_client'));
            }

            if ($entityType === 'merchant' && $entity->client_id !== $clientId) {
                throw new \Exception(__('messages.merchant_must_belong_to_your_client'));
            }
        } elseif ($currentUser->isHeadQuarterUser()) {
            if (!in_array($role, self::MERCHANT_ROLES)) {
                throw new \Exception(__('messages.head_quarter_can_only_create_merchant_users'));
            }

            if ($entityType !== 'merchant') {
                throw new \Exception(__('messages.entity_type_must_be_merchant'));
            }

            $headQuarterId = $currentUser->getHeadQuarterId();
            $entity = Merchant::findOrFail($entityId);

            if ($entity->head_quarter_id !== $headQuarterId) {
                throw new \Exception(__('messages.merchant_must_belong_to_your_head_quarter'));
            }
        }
    }

    private function canAccessUser(User $currentUser, User $targetUser): bool
    {
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

    private function resolveEntityClass(string $entityType): string
    {
        return match($entityType) {
            'client' => Client::class,
            'head_quarter' => HeadQuarter::class,
            'merchant' => Merchant::class,
            default => throw new \Exception(__('messages.invalid_entity_type')),
        };
    }

    private function getEntity(string $entityClass, int $entityId)
    {
        return $entityClass::findOrFail($entityId);
    }
}
