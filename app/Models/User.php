<?php

namespace App\Models;

use App\Traits\HasSingleRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
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

    public function role()
    {
        return $this->roles()->first();
    }

    public function getRoleNameAttribute(): ?string
    {
        return $this->role()?->name;
    }

    public function getRoleGuardNameAttribute(): ?string
    {
        return $this->role()?->guard_name;
    }

    public function assignRole($role, $guard = null): static
    {
        $this->roles()->detach();

        return parent::assignRole($role, $guard);
    }

    public function syncRoles(...$roles): static
    {
        if (count($roles) > 1) {
            $roles = [$roles[0]];
        }

        return parent::syncRoles($roles);
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
        return $this->hasExactRole('system_owner');
    }

    public function isClientUser(): bool
    {
        return $this->hasExactRole('client');
    }

    public function isHeadOfficeUser(): bool
    {
        return $this->hasExactRole('head_office');
    }

    public function isMerchantUser(): bool
    {
        return $this->hasExactRole('merchant');
    }
}
