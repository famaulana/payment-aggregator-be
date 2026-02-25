<?php

namespace App\Models;

use App\Enums\WebhookDirection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentWebhookLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'direction',
        'event_type',
        'transaction_id',
        'gateway_code',
        'target_url',
        'payload',
        'response_status',
        'response_body',
        'attempt_count',
        'is_verified',
        'processed_at',
    ];

    protected $casts = [
        'direction' => WebhookDirection::class,
        'payload' => 'array',
        'response_body' => 'array',
        'attempt_count' => 'integer',
        'response_status' => 'integer',
        'is_verified' => 'boolean',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
