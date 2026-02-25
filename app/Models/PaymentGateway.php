<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    use HasFactory;

    protected $fillable = [
        'pg_code',
        'pg_name',
        'api_url',
        'sandbox_url',
        'api_key_encrypted',
        'api_secret_encrypted',
        'webhook_secret_encrypted',
        'supported_methods',
        'status',
        'environment',
        'settlement_sla',
        'priority',
        'created_by',
    ];

    protected $casts = [
        'supported_methods' => 'array',
        'settlement_sla' => 'integer',
        'priority' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key_encrypted',
        'api_secret_encrypted',
        'webhook_secret_encrypted',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getApiUrl(): string
    {
        if ($this->environment === 'production') {
            return $this->api_url;
        }
        return $this->sandbox_url ?? $this->api_url;
    }

    public function getApiKey(): string
    {
        return decrypt($this->api_key_encrypted);
    }

    public function getApiSecret(): string
    {
        return decrypt($this->api_secret_encrypted);
    }

    public function getWebhookSecret(): ?string
    {
        if (!$this->webhook_secret_encrypted) {
            return null;
        }
        return decrypt($this->webhook_secret_encrypted);
    }

    public function paymentMethodMappings()
    {
        return $this->hasMany(PgPaymentMethodMapping::class);
    }

    public function mdrConfigurations()
    {
        return $this->hasMany(MdrConfiguration::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function gatewayLogs()
    {
        return $this->hasMany(PaymentGatewayLog::class);
    }
}
