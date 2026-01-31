<?php

namespace App\Models;

use App\Enums\ApiKeyStatus;
use App\Enums\Environment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'key_name',
        'api_key',
        'api_key_hashed',
        'api_secret_hashed',
        'environment',
        'status',
        'ip_whitelist',
        'rate_limit_per_minute',
        'rate_limit_per_hour',
        'last_used_at',
        'total_requests',
        'notes',
        'created_by',
        'revoked_by',
        'revoked_at',
    ];

    protected $casts = [
        'status' => ApiKeyStatus::class,
        'environment' => Environment::class,
        'ip_whitelist' => 'array',
        'rate_limit_per_minute' => 'integer',
        'rate_limit_per_hour' => 'integer',
        'total_requests' => 'integer',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function revoker()
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function apiRequestLogs()
    {
        return $this->hasMany(ApiRequestLog::class);
    }
}
