<?php

namespace App\Models;

use App\Enums\PaymentMethodType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'method_code',
        'method_name',
        'method_type',
        'status',
        'icon_url',
        'min_amount',
        'max_amount',
        'is_redirect_based',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'method_type' => PaymentMethodType::class,
        'min_amount' => 'integer',
        'max_amount' => 'integer',
        'is_redirect_based' => 'boolean',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function paymentGatewayMappings()
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
}
