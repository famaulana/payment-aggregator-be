<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApiRequestLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'api_key_id',
        'client_id',
        'endpoint',
        'method',
        'request_headers',
        'request_body',
        'response_status',
        'response_body',
        'processing_time_ms',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_body' => 'array',
        'processing_time_ms' => 'integer',
        'response_status' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function apiKey()
    {
        return $this->belongsTo(ApiKey::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
