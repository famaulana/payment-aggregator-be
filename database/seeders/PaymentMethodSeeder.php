<?php

namespace Database\Seeders;

use App\Models\MdrConfiguration;
use App\Models\PaymentGateway;
use App\Models\PaymentMethod;
use App\Models\PgPaymentMethodMapping;
use Illuminate\Database\Seeder;

class PaymentMethodSeeder extends Seeder
{
    public function run(): void
    {
        $bayarind = PaymentGateway::where('pg_code', 'bayarind')->first();

        if (!$bayarind) {
            $this->command->error('Run PaymentGatewaySeeder first!');
            return;
        }

        /**
         * Fee structure per the pricing table:
         * vendor_margin_* = MODAL (cost from PG)
         * fee_* (in pg_payment_method_mapping) = HARGA JUAL (charged to client)
         * our_margin = KEUNTUNGAN (profit = harga jual - modal)
         *
         * MDR = total charged to client (harga jual)
         */
        $methods = [

            // ─────────────────────────────────────────────────────────────────
            // VIRTUAL ACCOUNT — Bayarind
            // Modal: Rp 3,500 | Harga Jual: Rp 4,500 | Untung: Rp 1,000
            // ─────────────────────────────────────────────────────────────────
            [
                'method' => [
                    'method_code'  => 'va_bca',
                    'method_name'  => 'BCA Virtual Account',
                    'method_type'  => 'virtual_account',
                    'status'       => 'active',
                    'min_amount'   => 10000,
                    'max_amount'   => 50000000,
                    'sort_order'   => 10,
                ],
                'gateway'  => $bayarind,
                'mapping'  => [
                    'pg_method_code'          => 'VA_BCA',
                    'vendor_margin_type'      => 'fixed',
                    'vendor_margin_fixed'     => 3500.00,
                    'vendor_margin_percentage' => 0,
                    'status'                  => 'active',
                    'fee_type'                => 'fixed',
                    'fee_fixed'               => 4500.00,
                    'fee_percentage'          => 0,
                    'is_primary'              => true,
                    'min_amount'              => 10000,
                    'max_amount'              => 50000000,
                ],
                'mdr' => [
                    'our_margin_type'       => 'fixed',
                    'our_margin_fixed'      => 1000.00,
                    'our_margin_percentage' => 0,
                    'mdr_total_percentage'  => 0,
                    'is_active'             => true,
                ],
            ],
            [
                'method' => [
                    'method_code'  => 'va_mandiri',
                    'method_name'  => 'Mandiri Virtual Account',
                    'method_type'  => 'virtual_account',
                    'status'       => 'active',
                    'min_amount'   => 10000,
                    'max_amount'   => 50000000,
                    'sort_order'   => 11,
                ],
                'gateway'  => $bayarind,
                'mapping'  => [
                    'pg_method_code'          => 'VA_MANDIRI',
                    'vendor_margin_type'      => 'fixed',
                    'vendor_margin_fixed'     => 3500.00,
                    'vendor_margin_percentage' => 0,
                    'status'                  => 'active',
                    'fee_type'                => 'fixed',
                    'fee_fixed'               => 4500.00,
                    'fee_percentage'          => 0,
                    'is_primary'              => true,
                    'min_amount'              => 10000,
                    'max_amount'              => 50000000,
                ],
                'mdr' => [
                    'our_margin_type'       => 'fixed',
                    'our_margin_fixed'      => 1000.00,
                    'our_margin_percentage' => 0,
                    'mdr_total_percentage'  => 0,
                    'is_active'             => true,
                ],
            ],
            [
                'method' => [
                    'method_code'  => 'va_bri',
                    'method_name'  => 'BRI Virtual Account',
                    'method_type'  => 'virtual_account',
                    'status'       => 'active',
                    'min_amount'   => 10000,
                    'max_amount'   => 50000000,
                    'sort_order'   => 12,
                ],
                'gateway'  => $bayarind,
                'mapping'  => [
                    'pg_method_code'          => 'VA_BRI',
                    'vendor_margin_type'      => 'fixed',
                    'vendor_margin_fixed'     => 3500.00,
                    'vendor_margin_percentage' => 0,
                    'status'                  => 'active',
                    'fee_type'                => 'fixed',
                    'fee_fixed'               => 4500.00,
                    'fee_percentage'          => 0,
                    'is_primary'              => true,
                    'min_amount'              => 10000,
                    'max_amount'              => 50000000,
                ],
                'mdr' => [
                    'our_margin_type'       => 'fixed',
                    'our_margin_fixed'      => 1000.00,
                    'our_margin_percentage' => 0,
                    'mdr_total_percentage'  => 0,
                    'is_active'             => true,
                ],
            ],
            [
                'method' => [
                    'method_code'  => 'va_bni',
                    'method_name'  => 'BNI Virtual Account',
                    'method_type'  => 'virtual_account',
                    'status'       => 'active',
                    'min_amount'   => 10000,
                    'max_amount'   => 50000000,
                    'sort_order'   => 13,
                ],
                'gateway'  => $bayarind,
                'mapping'  => [
                    'pg_method_code'          => 'VA_BNI',
                    'vendor_margin_type'      => 'fixed',
                    'vendor_margin_fixed'     => 3500.00,
                    'vendor_margin_percentage' => 0,
                    'status'                  => 'active',
                    'fee_type'                => 'fixed',
                    'fee_fixed'               => 4500.00,
                    'fee_percentage'          => 0,
                    'is_primary'              => true,
                    'min_amount'              => 10000,
                    'max_amount'              => 50000000,
                ],
                'mdr' => [
                    'our_margin_type'       => 'fixed',
                    'our_margin_fixed'      => 1000.00,
                    'our_margin_percentage' => 0,
                    'mdr_total_percentage'  => 0,
                    'is_active'             => true,
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // QRIS — Bayarind
            // Modal: 0.50% | Harga Jual: 0.70% | Untung: 0.20%
            // ─────────────────────────────────────────────────────────────────
            [
                'method' => [
                    'method_code'  => 'qris',
                    'method_name'  => 'QRIS',
                    'method_type'  => 'qris',
                    'status'       => 'inactive',
                    'min_amount'   => 1000,
                    'max_amount'   => 10000000,
                    'sort_order'   => 1,
                ],
                'gateway'  => $bayarind,
                'mapping'  => [
                    'pg_method_code'          => 'QRIS',
                    'vendor_margin_type'      => 'percentage',
                    'vendor_margin_percentage' => 0.50,
                    'vendor_margin_fixed'     => 0,
                    'status'                  => 'active',
                    'fee_type'                => 'percentage',
                    'fee_percentage'          => 0.70,
                    'fee_fixed'               => 0,
                    'is_primary'              => true,
                    'min_amount'              => 1000,
                    'max_amount'              => 10000000,
                ],
                'mdr' => [
                    'our_margin_type'       => 'percentage',
                    'our_margin_percentage' => 0.20,
                    'our_margin_fixed'      => 0,
                    'mdr_total_percentage'  => 0.70,
                    'is_active'             => true,
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // E-WALLET — Bayarind
            // ─────────────────────────────────────────────────────────────────
            // DANA: Modal 1.50% | Jual 2.50% | Untung 1.00%
            [
                'method' => [
                    'method_code'       => 'dana',
                    'method_name'       => 'DANA',
                    'method_type'       => 'e_wallet',
                    'status'            => 'inactive',
                    'min_amount'        => 1000,
                    'max_amount'        => 20000000,
                    'is_redirect_based' => true,
                    'sort_order'        => 20,
                ],
                'gateway'  => $bayarind,
                'mapping'  => [
                    'pg_method_code'          => 'DANA',
                    'vendor_margin_type'      => 'percentage',
                    'vendor_margin_percentage' => 1.50,
                    'vendor_margin_fixed'     => 0,
                    'status'                  => 'active',
                    'fee_type'                => 'percentage',
                    'fee_percentage'          => 2.50,
                    'fee_fixed'               => 0,
                    'is_primary'              => true,
                ],
                'mdr' => [
                    'our_margin_type'       => 'percentage',
                    'our_margin_percentage' => 1.00,
                    'our_margin_fixed'      => 0,
                    'mdr_total_percentage'  => 2.50,
                    'is_active'             => true,
                ],
            ],
            // OVO (Non-Digital): Modal 1.67% | Jual 2.50% | Untung 0.83%
            [
                'method' => [
                    'method_code'       => 'ovo',
                    'method_name'       => 'OVO',
                    'method_type'       => 'e_wallet',
                    'status'            => 'inactive',
                    'min_amount'        => 1000,
                    'max_amount'        => 20000000,
                    'is_redirect_based' => true,
                    'sort_order'        => 21,
                ],
                'gateway'  => $bayarind,
                'mapping'  => [
                    'pg_method_code'          => 'OVO',
                    'vendor_margin_type'      => 'percentage',
                    'vendor_margin_percentage' => 1.67,
                    'vendor_margin_fixed'     => 0,
                    'status'                  => 'active',
                    'fee_type'                => 'percentage',
                    'fee_percentage'          => 2.50,
                    'fee_fixed'               => 0,
                    'is_primary'              => true,
                ],
                'mdr' => [
                    'our_margin_type'       => 'percentage',
                    'our_margin_percentage' => 0.83,
                    'our_margin_fixed'      => 0,
                    'mdr_total_percentage'  => 2.50,
                    'is_active'             => true,
                ],
            ],
            // ShopeePay: Modal 2.00% | Jual 2.50% | Untung 0.50%
            [
                'method' => [
                    'method_code'       => 'shopeepay',
                    'method_name'       => 'ShopeePay',
                    'method_type'       => 'e_wallet',
                    'status'            => 'inactive',
                    'min_amount'        => 1000,
                    'max_amount'        => 20000000,
                    'is_redirect_based' => true,
                    'sort_order'        => 22,
                ],
                'gateway'  => $bayarind,
                'mapping'  => [
                    'pg_method_code'          => 'SHOPEEPAY',
                    'vendor_margin_type'      => 'percentage',
                    'vendor_margin_percentage' => 2.00,
                    'vendor_margin_fixed'     => 0,
                    'status'                  => 'active',
                    'fee_type'                => 'percentage',
                    'fee_percentage'          => 2.50,
                    'fee_fixed'               => 0,
                    'is_primary'              => true,
                ],
                'mdr' => [
                    'our_margin_type'       => 'percentage',
                    'our_margin_percentage' => 0.50,
                    'our_margin_fixed'      => 0,
                    'mdr_total_percentage'  => 2.50,
                    'is_active'             => true,
                ],
            ],

            // ─────────────────────────────────────────────────────────────────
            // PAY LATER — Bayarind
            // AkuLaku: Modal 2.30% | Jual 3.00% | Untung 0.70%
            // Kredivo:  Modal 2.30% | Jual 3.00% | Untung 0.70%
            // ─────────────────────────────────────────────────────────────────
            [
                'method' => [
                    'method_code'       => 'akulaku',
                    'method_name'       => 'AkuLaku',
                    'method_type'       => 'paylater',
                    'status'            => 'inactive',
                    'min_amount'        => 50000,
                    'max_amount'        => 50000000,
                    'is_redirect_based' => true,
                    'sort_order'        => 40,
                ],
                'gateway'  => $bayarind,
                'mapping'  => [
                    'pg_method_code'          => 'AKULAKU',
                    'vendor_margin_type'      => 'percentage',
                    'vendor_margin_percentage' => 2.30,
                    'vendor_margin_fixed'     => 0,
                    'status'                  => 'active',
                    'fee_type'                => 'percentage',
                    'fee_percentage'          => 3.00,
                    'fee_fixed'               => 0,
                    'is_primary'              => true,
                ],
                'mdr' => [
                    'our_margin_type'       => 'percentage',
                    'our_margin_percentage' => 0.70,
                    'our_margin_fixed'      => 0,
                    'mdr_total_percentage'  => 3.00,
                    'is_active'             => true,
                ],
            ],
            [
                'method' => [
                    'method_code'       => 'kredivo',
                    'method_name'       => 'Kredivo',
                    'method_type'       => 'paylater',
                    'status'            => 'inactive',
                    'min_amount'        => 100000,
                    'max_amount'        => 50000000,
                    'is_redirect_based' => true,
                    'sort_order'        => 41,
                ],
                'gateway'  => $bayarind,
                'mapping'  => [
                    'pg_method_code'          => 'KREDIVO',
                    'vendor_margin_type'      => 'percentage',
                    'vendor_margin_percentage' => 2.30,
                    'vendor_margin_fixed'     => 0,
                    'status'                  => 'active',
                    'fee_type'                => 'percentage',
                    'fee_percentage'          => 3.00,
                    'fee_fixed'               => 0,
                    'is_primary'              => true,
                ],
                'mdr' => [
                    'our_margin_type'       => 'percentage',
                    'our_margin_percentage' => 0.70,
                    'our_margin_fixed'      => 0,
                    'mdr_total_percentage'  => 3.00,
                    'is_active'             => true,
                ],
            ],
        ];

        foreach ($methods as $item) {
            // Create or update payment method
            $paymentMethod = PaymentMethod::updateOrCreate(
                ['method_code' => $item['method']['method_code']],
                $item['method']
            );

            // Create or update PG mapping
            $mappingData = array_merge($item['mapping'], [
                'payment_gateway_id' => $item['gateway']->id,
                'payment_method_id'  => $paymentMethod->id,
            ]);

            PgPaymentMethodMapping::updateOrCreate(
                [
                    'payment_gateway_id' => $item['gateway']->id,
                    'payment_method_id'  => $paymentMethod->id,
                ],
                $mappingData
            );

            // Create or update MDR configuration
            $mdrData = array_merge($item['mdr'], [
                'payment_method_id'  => $paymentMethod->id,
                'payment_gateway_id' => $item['gateway']->id,
                'effective_from'     => now(),
            ]);

            // Deactivate old configs and insert new one
            MdrConfiguration::where('payment_method_id', $paymentMethod->id)
                ->where('payment_gateway_id', $item['gateway']->id)
                ->update(['is_active' => false]);

            MdrConfiguration::create($mdrData);
        }

        $this->command->info('Payment methods and fees seeded successfully (' . count($methods) . ' methods).');
    }
}
