<?php

namespace Database\Seeders;

use App\Models\PaymentGateway;
use Illuminate\Database\Seeder;

class PaymentGatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'pg_code'               => 'bayarind',
                'pg_name'               => 'Bayarind',
                'api_url'               => 'https://api.bayarind.id',
                'sandbox_url'           => 'https://sandbox.bayarind.id',
                'api_key_encrypted'     => encrypt('BAYARIND_API_KEY_PLACEHOLDER'),
                'api_secret_encrypted'  => encrypt('BAYARIND_API_SECRET_PLACEHOLDER'),
                'webhook_secret_encrypted' => encrypt('BAYARIND_WEBHOOK_SECRET_PLACEHOLDER'),
                'supported_methods'     => json_encode([
                    'qris', 'va_bca', 'va_mandiri', 'va_bri', 'va_bni',
                    'dana', 'ovo', 'shopeepay', 'akulaku', 'kredivo',
                ]),
                'status'                => 'active',
                'environment'           => 'dev',
                'settlement_sla'        => 1,
                'priority'              => 10,
            ],
        ];

        foreach ($gateways as $gateway) {
            PaymentGateway::updateOrCreate(
                ['pg_code' => $gateway['pg_code']],
                $gateway
            );
        }

        $this->command->info('Payment gateways seeded: Bayarind');
    }
}
