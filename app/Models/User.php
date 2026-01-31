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

    protected $fillable = [
        'username',
        'email',
        'password',
        'full_name',
        'client_id',
        'head_office_id',
        'merchant_id',
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

    public function scopeRole($query, $role)
    {
        return $query->whereHas('roles', function ($q) use ($role) {
            $q->where('name', $role);
        });
    }

    public function scopeClientUsers($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    public function scopeHeadOfficeUsers($query, $headOfficeId)
    {
        return $query->where('head_office_id', $headOfficeId);
    }

    public function scopeMerchantUsers($query, $merchantId)
    {
        return $query->where('merchant_id', $merchantId);
    }

    public function hasExactRole($role): bool
    {
        $userRole = $this->role();
        return $userRole && $userRole->name === $role;
    }

    public function isSystemOwner(): bool
    {
        return $this->hasExactRole('system_owner')
            || $this->hasRole('system_owner_admin')
            || $this->hasRole('system_owner_finance')
            || $this->hasRole('system_owner_support');
    }

    public function isClientUser(): bool
    {
        return $this->hasExactRole('client')
            || $this->hasRole('client_admin')
            || $this->hasRole('client_finance')
            || $this->hasRole('client_operations');
    }

    public function isHeadOfficeUser(): bool
    {
        return $this->hasExactRole('head_office')
            || $this->hasRole('head_office_admin')
            || $this->hasRole('head_office_supervisor');
    }

    public function isMerchantUser(): bool
    {
        return $this->hasExactRole('merchant')
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

        if ($entityType === 'client') {
            if ($this->isClientUser()) {
                return $this->client_id === $entityId;
            }
            if ($this->isHeadOfficeUser() || $this->isMerchantUser()) {
                return $this->client_id === $entityId;
            }
        }

        if ($entityType === 'head_office') {
            if ($this->isClientUser()) {
                return \App\Models\HeadOffice::where('client_id', $this->client_id)
                    ->where('id', $entityId)
                    ->exists();
            }
            if ($this->isHeadOfficeUser()) {
                return $this->head_office_id === $entityId;
            }
            if ($this->isMerchantUser()) {
                return $this->head_office_id === $entityId;
            }
        }

        if ($entityType === 'merchant') {
            if ($this->isClientUser()) {
                return \App\Models\Merchant::where('client_id', $this->client_id)
                    ->where('id', $entityId)
                    ->exists();
            }
            if ($this->isHeadOfficeUser()) {
                return \App\Models\Merchant::where('head_office_id', $this->head_office_id)
                    ->where('id', $entityId)
                    ->exists();
            }
            if ($this->isMerchantUser()) {
                return $this->merchant_id === $entityId;
            }
        }

        return false;
    }
}
