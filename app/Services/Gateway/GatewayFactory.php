<?php

namespace App\Services\Gateway;

use App\Models\PaymentGateway;
use App\Services\Gateway\Bayarind\BayarindGateway;
use App\Services\Gateway\Contracts\PaymentGatewayInterface;
use RuntimeException;

class GatewayFactory
{
    public static function make(PaymentGateway $gateway): PaymentGatewayInterface
    {
        return match ($gateway->pg_code) {
            'bayarind' => new BayarindGateway($gateway),
            default    => throw new RuntimeException("Unsupported payment gateway: {$gateway->pg_code}"),
        };
    }

    public static function makeByCode(string $pgCode): PaymentGatewayInterface
    {
        $gateway = PaymentGateway::where('pg_code', $pgCode)
            ->where('status', 'active')
            ->firstOrFail();

        return self::make($gateway);
    }
}
