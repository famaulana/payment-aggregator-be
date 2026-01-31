<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $jakarta = DB::table('provinces')->where('code', '31')->first();
        $jakartaSelatan = DB::table('cities')->where('code', '3171')->first();

        Client::updateOrCreate(
            ['client_code' => 'JDP001'],
            [
                'client_name' => 'PT Jago Digital Payment',
                'business_type' => 'Financial Technology',
                'kyb_status' => 'approved',
                'kyb_submitted_at' => now()->subDays(30),
                'kyb_approved_at' => now()->subDays(25),
                'settlement_time' => '14:00:00',
                'settlement_config' => json_encode([
                    'auto_settlement' => true,
                    'settlement_cycle' => 'daily',
                    'min_settlement_amount' => 100000,
                ]),
                'bank_name' => 'Bank Mandiri',
                'bank_account_number' => '1370001234567',
                'bank_account_holder_name' => 'PT Jago Digital Payment',
                'bank_branch' => 'KCP Jakarta Sudirman',
                'pic_name' => 'Budi Santoso',
                'pic_position' => 'Finance Director',
                'pic_phone' => '081234567890',
                'pic_email' => 'budi.santoso@jdp.co.id',
                'company_phone' => '021-5551234',
                'company_email' => 'info@jdp.co.id',
                'available_balance' => 50000000.00,
                'pending_balance' => 5000000.00,
                'minus_balance' => 0.00,
                'province_id' => $jakarta->id ?? null,
                'city_id' => $jakartaSelatan->id ?? null,
                'address' => 'Jl. Jend. Sudirman Kav. 52-53, SCBD',
                'postal_code' => '12190',
                'status' => 'active',
            ]
        );
    }
}
