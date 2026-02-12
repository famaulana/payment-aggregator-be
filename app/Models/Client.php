<?php

namespace App\Models;

use App\Enums\ClientStatus;
use App\Enums\KybStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $fillable = [
        'system_owner_id',
        'client_code',
        'client_name',
        'business_type',
        'kyb_status',
        'kyb_submitted_at',
        'kyb_approved_at',
        'kyb_rejected_at',
        'kyb_rejection_reason',
        'settlement_time',
        'settlement_config',
        'bank_name',
        'bank_account_number',
        'bank_account_holder_name',
        'bank_branch',
        'pic_name',
        'pic_position',
        'pic_phone',
        'pic_email',
        'company_phone',
        'company_email',
        'available_balance',
        'pending_balance',
        'minus_balance',
        'province_id',
        'city_id',
        'address',
        'postal_code',
        'status',
        'created_by',
    ];

    protected $casts = [
        'kyb_status' => KybStatus::class,
        'status' => ClientStatus::class,
        'kyb_submitted_at' => 'datetime',
        'kyb_approved_at' => 'datetime',
        'kyb_rejected_at' => 'datetime',
        'settlement_time' => 'datetime:H:i',
        'settlement_config' => 'array',
        'available_balance' => 'decimal:2',
        'pending_balance' => 'decimal:2',
        'minus_balance' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function systemOwner()
    {
        return $this->belongsTo(SystemOwner::class);
    }

    public function kyb()
    {
        return $this->hasOne(ClientKyb::class);
    }

    public function headQuarters()
    {
        return $this->hasMany(HeadQuarter::class);
    }

    public function merchants()
    {
        return $this->hasMany(Merchant::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function settlementBatches()
    {
        return $this->hasMany(SettlementBatch::class);
    }

    public function apiKeys()
    {
        return $this->hasMany(ApiKey::class);
    }

    public function balances()
    {
        return $this->hasMany(ClientBalance::class);
    }

    public function users()
    {
        return $this->morphMany(User::class, 'entity');
    }
}
