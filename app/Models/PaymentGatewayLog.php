<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentGatewayLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'payment_gateway_id',
        'action',
        'request_url',
        'request_method',
        'request_headers',
        'request_body',
        'response_status',
        'response_body',
        'processing_time_ms',
        'is_success',
    ];

    protected $casts = [
        'request_headers' => 'array',
        'request_body' => 'array',
        'response_body' => 'array',
        'response_status' => 'integer',
        'processing_time_ms' => 'integer',
        'is_success' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }
}
