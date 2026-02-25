<?php

namespace App\Models;

use App\Enums\MarginType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PgPaymentMethodMapping extends Model
{
    use HasFactory;

    protected $table = 'pg_payment_method_mapping';

    protected $fillable = [
        'payment_gateway_id',
        'payment_method_id',
        'pg_method_code',
        'vendor_margin_type',
        'vendor_margin_percentage',
        'vendor_margin_fixed',
        'status',
        'min_amount',
        'max_amount',
        'fee_type',
        'fee_fixed',
        'fee_percentage',
        'channel_config',
        'is_primary',
    ];

    protected $casts = [
        'vendor_margin_type' => MarginType::class,
        'vendor_margin_percentage' => 'decimal:2',
        'vendor_margin_fixed' => 'decimal:2',
        'min_amount' => 'integer',
        'max_amount' => 'integer',
        'fee_fixed' => 'decimal:2',
        'fee_percentage' => 'decimal:2',
        'channel_config' => 'array',
        'is_primary' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * Calculate the fee charged to client for given amount
     */
    public function calculateClientFee(float $amount): float
    {
        return match ($this->fee_type) {
            'fixed' => (float) $this->fee_fixed,
            'percentage' => round($amount * ((float) $this->fee_percentage / 100), 2),
            'mixed' => (float) $this->fee_fixed + round($amount * ((float) $this->fee_percentage / 100), 2),
            default => 0,
        };
    }
}
