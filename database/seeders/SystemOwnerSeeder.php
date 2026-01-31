<?php

namespace Database\Seeders;

use App\Models\SystemOwner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemOwnerSeeder extends Seeder
{
    public function run(): void
    {
        $jakarta = DB::table('provinces')->where('code', '31')->first();
        $jakartaSelatan = DB::table('cities')->where('code', '3171')->first();

        SystemOwner::updateOrCreate(
            ['code' => 'JDP'],
            [
                'name' => 'Jago Digital Payment',
                'business_type' => 'Payment Platform',
                'pic_name' => 'CEO JDP',
                'pic_position' => 'Chief Executive Officer',
                'pic_phone' => '081234567890',
                'pic_email' => 'ceo@jdp.co.id',
                'company_phone' => '021-5550000',
                'company_email' => 'info@jdp.co.id',
                'province_id' => $jakarta->id ?? null,
                'city_id' => $jakartaSelatan->id ?? null,
                'address' => 'Jl. Jend. Sudirman Kav. 52-53, SCBD',
                'postal_code' => '12190',
                'status' => 'active',
            ]
        );
    }
}
