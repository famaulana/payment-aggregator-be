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
        $banten = DB::table('provinces')->where('code', '36')->first();
        $jakartaSelatan = DB::table('cities')->where('code', '3171')->first();
        $jakartaPusat = DB::table('cities')->where('code', '3172')->first();
        $tangerang = DB::table('cities')->where('code', '3671')->first();

        Client::updateOrCreate(
            ['client_code' => 'DPI001'],
            [
                'client_name' => 'PT Digital Payment Indonesia',
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
                'bank_account_holder_name' => 'PT Digital Payment Indonesia',
                'bank_branch' => 'KCP Jakarta Sudirman',
                'pic_name' => 'Budi Santoso',
                'pic_position' => 'Finance Director',
                'pic_phone' => '081234567890',
                'pic_email' => 'budi.santoso@digitalpayment.co.id',
                'company_phone' => '021-5551234',
                'company_email' => 'info@digitalpayment.co.id',
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

        Client::updateOrCreate(
            ['client_code' => 'RET002'],
            [
                'client_name' => 'PT Retail Nusantara',
                'business_type' => 'Retail & E-commerce',
                'kyb_status' => 'approved',
                'kyb_submitted_at' => now()->subDays(20),
                'kyb_approved_at' => now()->subDays(15),
                'settlement_time' => '16:00:00',
                'settlement_config' => json_encode([
                    'auto_settlement' => true,
                    'settlement_cycle' => 'daily',
                    'min_settlement_amount' => 500000,
                ]),
                'bank_name' => 'Bank BCA',
                'bank_account_number' => '5420123456',
                'bank_account_holder_name' => 'PT Retail Nusantara',
                'bank_branch' => 'KCU Jakarta',
                'pic_name' => 'Siti Nurhaliza',
                'pic_position' => 'CFO',
                'pic_phone' => '081298765432',
                'pic_email' => 'siti@retailnusantara.com',
                'company_phone' => '021-5559876',
                'company_email' => 'contact@retailnusantara.com',
                'available_balance' => 75000000.00,
                'pending_balance' => 10000000.00,
                'minus_balance' => 0.00,
                'province_id' => $jakarta->id ?? null,
                'city_id' => $jakartaPusat->id ?? null,
                'address' => 'Plaza Indonesia, Jl. M.H. Thamrin No. 28-30',
                'postal_code' => '10350',
                'status' => 'active',
            ]
        );

        Client::updateOrCreate(
            ['client_code' => 'FNB003'],
            [
                'client_name' => 'PT Food Berkah',
                'business_type' => 'Food & Beverage',
                'kyb_status' => 'pending',
                'kyb_submitted_at' => now()->subDays(5),
                'settlement_time' => '15:00:00',
                'bank_name' => 'Bank BNI',
                'bank_account_number' => '0123456789',
                'bank_account_holder_name' => 'PT Food Berkah',
                'bank_branch' => 'KCP Tangerang',
                'pic_name' => 'Ahmad Wijaya',
                'pic_position' => 'Owner',
                'pic_phone' => '081345678901',
                'pic_email' => 'ahmad@foodberkah.com',
                'company_phone' => '021-5554321',
                'company_email' => 'info@foodberkah.com',
                'available_balance' => 5000000.00,
                'pending_balance' => 0.00,
                'minus_balance' => 0.00,
                'province_id' => $banten->id ?? null,
                'city_id' => $tangerang->id ?? null,
                'address' => 'Ruko Grand Alam Sutera Blok A No. 15',
                'postal_code' => '15320',
                'status' => 'active',
            ]
        );
    }
}
