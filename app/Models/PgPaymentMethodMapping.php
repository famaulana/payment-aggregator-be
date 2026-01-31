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
    ];

    protected $casts = [
        'vendor_margin_type' => MarginType::class,
        'vendor_margin_percentage' => 'decimal:2',
        'vendor_margin_fixed' => 'decimal:2',
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
}
