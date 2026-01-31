<?php

namespace App\Models;

use App\Enums\MarginType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MdrConfiguration extends Model
{
    use HasFactory;

    protected $table = 'mdr_configurations';

    protected $fillable = [
        'payment_method_id',
        'payment_gateway_id',
        'our_margin_type',
        'our_margin_percentage',
        'our_margin_fixed',
        'mdr_total_percentage',
        'effective_from',
        'effective_to',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'our_margin_type' => MarginType::class,
        'our_margin_percentage' => 'decimal:2',
        'our_margin_fixed' => 'decimal:2',
        'mdr_total_percentage' => 'decimal:2',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
