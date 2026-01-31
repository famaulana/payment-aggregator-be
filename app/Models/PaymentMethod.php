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
    ];

    protected $casts = [
        'method_type' => PaymentMethodType::class,
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
