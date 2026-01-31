<?php

namespace App\Models;

use App\Traits\HasSingleRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles, HasSingleRole;

    protected $guard_name = 'api';

    protected $appends = [
        'role_name',
        'permissions_list',
    ];

    protected $fillable = [
        'username',
        'email',
        'password',
        'full_name',
        'entity_type',
        'entity_id',
        'fcm_token',
        'status',
        'last_login_at',
        'created_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function assignRole($role, $guard = null): static
    {
        $this->roles()->detach();

        return parent::assignRole($role, $guard);
    }

    public function assignSingleRole($role): static
    {
        if (is_string($role)) {
            $guard = 'api';
            $role = \Spatie\Permission\Models\Role::findByName($role, $guard);
        }

        $this->roles()->sync([$role->id]);

        $this->forgetCachedPermissions();

        return $this;
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function headOffice()
    {
        return $this->belongsTo(HeadOffice::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function systemOwner()
    {
        return $this->belongsTo(SystemOwner::class);
    }

    public function entity()
    {
        return $this->morphTo();
    }

    public function getRoleNameAttribute(): string
    {
        return $this->roles->first()?->name ?? '';
    }

    public function getPermissionsListAttribute(): array
    {
        return $this->getAllPermissions()->pluck('name')->toArray();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdClients()
    {
        return $this->hasMany(Client::class, 'created_by');
    }

    public function createdMerchants()
    {
        return $this->hasMany(Merchant::class, 'created_by');
    }

    public function createdApiKeys()
    {
        return $this->hasMany(ApiKey::class, 'created_by');
    }

    public function revokedApiKeys()
    {
        return $this->hasMany(ApiKey::class, 'revoked_by');
    }

    public function approvedSettlementBatches()
    {
        return $this->hasMany(SettlementBatch::class, 'approved_by');
    }

    public function rejectedSettlementBatches()
    {
        return $this->hasMany(SettlementBatch::class, 'rejected_by');
    }

    public function processedSettlementBatches()
    {
        return $this->hasMany(SettlementBatch::class, 'payout_processed_by');
    }

    public function overriddenTransactions()
    {
        return $this->hasMany(Transaction::class, 'overridden_by');
    }

    public function floatingFunds()
    {
        return $this->hasMany(FloatingFund::class, 'created_by');
    }

    public function clientBalances()
    {
        return $this->hasMany(ClientBalance::class, 'created_by');
    }

    public function reconciliationBatches()
    {
        return $this->hasMany(ReconciliationBatch::class, 'created_by');
    }

    public function resolvedReconciliations()
    {
        return $this->hasMany(Reconciliation::class, 'resolved_by');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function auditTrails()
    {
        return $this->hasMany(AuditTrail::class);
    }

    public function scopeRoleFilter($query, $role)
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        });
    }

    /**
     * Get the user's primary role
     */
    public function role()
    {
        return $this->roles()->first();
    }

    public function scopeClientUsers($query, $clientId)
    {
        return $query->where('entity_type', Client::class)
            ->where('entity_id', $clientId);
    }

    public function scopeHeadOfficeUsers($query, $headOfficeId)
    {
        return $query->where('entity_type', HeadOffice::class)
            ->where('entity_id', $headOfficeId);
    }

    public function scopeMerchantUsers($query, $merchantId)
    {
        return $query->where('entity_type', Merchant::class)
            ->where('entity_id', $merchantId);
    }

    public function scopeSystemOwnerUsers($query, $systemOwnerId)
    {
        return $query->where('entity_type', SystemOwner::class)
            ->where('entity_id', $systemOwnerId);
    }

    public function hasExactRole($role): bool
    {
        $userRole = $this->role();
        return $userRole && $userRole->name === $role;
    }

    public function isSystemOwner(): bool
    {
        return $this->entity_type === SystemOwner::class
            || $this->hasAnySystemOwnerRole();
    }

    /**
     * Check if user has any system owner related role
     */
    public function hasAnySystemOwnerRole(): bool
    {
        // Get all roles that start with 'system_owner_'
        $systemOwnerRoles = $this->roles->filter(function ($role) {
            return strpos($role->name, 'system_owner_') === 0;
        });

        return $systemOwnerRoles->count() > 0 || $this->hasExactRole('system_owner');
    }

    /**
     * Check if user has a specific system owner role
     */
    public function hasSystemOwnerRole(string $specificRole): bool
    {
        return $this->hasRole('system_owner') || $this->hasRole($specificRole);
    }

    public function isClientUser(): bool
    {
        return $this->entity_type === Client::class
            || $this->hasExactRole('client')
            || $this->hasRole('client_admin')
            || $this->hasRole('client_finance')
            || $this->hasRole('client_operations');
    }

    public function isHeadOfficeUser(): bool
    {
        return $this->entity_type === HeadOffice::class
            || $this->hasExactRole('head_office')
            || $this->hasRole('head_office_admin')
            || $this->hasRole('head_office_supervisor');
    }

    public function isMerchantUser(): bool
    {
        return $this->entity_type === Merchant::class
            || $this->hasExactRole('merchant')
            || $this->hasRole('merchant_admin')
            || $this->hasRole('merchant_cashier');
    }

    public function getParentRole(): ?Role
    {
        $role = $this->role();
        if (!$role) {
            return null;
        }

        $parentRoleName = $role->parent_role;
        if (!$parentRoleName) {
            return null;
        }

        return Role::where('name', $parentRoleName)->first();
    }

    public function getAllRoles(): \Illuminate\Database\Eloquent\Collection
    {
        $roles = collect([$this->role()]);

        $parentRole = $this->getParentRole();
        while ($parentRole) {
            $roles->push($parentRole);
            $parentRoleName = $parentRole->parent_role;
            $parentRole = $parentRoleName ? Role::where('name', $parentRoleName)->first() : null;
        }

        return $roles;
    }

    public function hasPermissionThroughParent(string $permission): bool
    {
        if ($this->can($permission)) {
            return true;
        }

        $allRoles = $this->getAllRoles();
        foreach ($allRoles as $role) {
            if ($role->hasPermissionTo($permission)) {
                return true;
            }
        }

        return false;
    }

    public function canAccessEntity($entityType, $entityId): bool
    {
        if ($this->isSystemOwner()) {
            return true;
        }

        $userEntity = $this->entity;

        if ($entityType === 'system_owner') {
            return $this->entity_type === SystemOwner::class && $this->entity_id === $entityId;
        }

        if ($entityType === 'client') {
            if ($this->entity_type === SystemOwner::class) {
                return true;
            }
            if ($this->entity_type === Client::class) {
                return $this->entity_id === $entityId;
            }
            if ($this->entity_type === HeadOffice::class && $userEntity) {
                return $userEntity->client_id === $entityId;
            }
            if ($this->entity_type === Merchant::class && $userEntity) {
                return $userEntity->client_id === $entityId;
            }
        }

        if ($entityType === 'head_office') {
            if ($this->entity_type === SystemOwner::class) {
                return true;
            }
            if ($this->entity_type === Client::class && $userEntity) {
                return \App\Models\HeadOffice::where('client_id', $userEntity->id)
                    ->where('id', $entityId)
                    ->exists();
            }
            if ($this->entity_type === HeadOffice::class) {
                return $this->entity_id === $entityId;
            }
            if ($this->entity_type === Merchant::class && $userEntity) {
                return $userEntity->head_office_id === $entityId;
            }
        }

        if ($entityType === 'merchant') {
            if ($this->entity_type === SystemOwner::class) {
                return true;
            }
            if ($this->entity_type === Client::class && $userEntity) {
                return \App\Models\Merchant::where('client_id', $userEntity->id)
                    ->where('id', $entityId)
                    ->exists();
            }
            if ($this->entity_type === HeadOffice::class && $userEntity) {
                return \App\Models\Merchant::where('head_office_id', $userEntity->id)
                    ->where('id', $entityId)
                    ->exists();
            }
            if ($this->entity_type === Merchant::class) {
                return $this->entity_id === $entityId;
            }
        }

        return false;
    }

    public function getEntityTypeLabel(): string
    {
        if ($this->entity_type === SystemOwner::class) {
            return 'System Owner';
        }
        if ($this->entity_type === Client::class) {
            return 'Client';
        }
        if ($this->entity_type === HeadOffice::class) {
            return 'Head Office';
        }
        if ($this->entity_type === Merchant::class) {
            return 'Merchant';
        }

        return 'Unknown';
    }

    public function getEntityName(): string
    {
        $entity = $this->entity;

        if (!$entity) {
            return 'N/A';
        }

        if ($entity instanceof SystemOwner) {
            return $entity->name;
        }
        if ($entity instanceof Client) {
            return $entity->client_name;
        }
        if ($entity instanceof HeadOffice) {
            return $entity->name;
        }
        if ($entity instanceof Merchant) {
            return $entity->merchant_name;
        }

        return 'N/A';
    }

    public function getClientId(): ?int
    {
        $entity = $this->entity;

        if ($entity instanceof SystemOwner) {
            return null;
        }
        if ($entity instanceof Client) {
            return $entity->id;
        }
        if ($entity instanceof HeadOffice) {
            return $entity->client_id;
        }
        if ($entity instanceof Merchant) {
            return $entity->client_id;
        }

        return null;
    }

    public function getHeadOfficeId(): ?int
    {
        $entity = $this->entity;

        if ($entity instanceof HeadOffice) {
            return $entity->id;
        }
        if ($entity instanceof Merchant) {
            return $entity->head_office_id;
        }

        return null;
    }

    public function getMerchantId(): ?int
    {
        if ($this->entity_type === Merchant::class) {
            return $this->entity_id;
        }

        return null;
    }
}
