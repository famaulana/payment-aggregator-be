<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\HeadQuarter;
use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MerchantSeeder extends Seeder
{
    public function run(): void
    {
        $jakarta = DB::table('provinces')->where('code', '31')->first();
        $jawaTimur = DB::table('provinces')->where('code', '35')->first();
        $jawaBarat = DB::table('provinces')->where('code', '32')->first();
        $jakartaSelatan = DB::table('cities')->where('code', '3171')->first();
        $surabaya = DB::table('cities')->where('code', '3578')->first();
        $bandung = DB::table('cities')->where('code', '3273')->first();

        $kebayoranBaru = $jakartaSelatan ? DB::table('districts')
            ->where('city_id', $jakartaSelatan->id)
            ->where('name', 'like', '%Kebayoran Baru%')
            ->first() : null;

        $kramatPela = $kebayoranBaru ? DB::table('sub_districts')
            ->where('district_id', $kebayoranBaru->id)
            ->where('name', 'like', '%Kramat Pela%')
            ->first() : null;

        $client = Client::where('client_code', 'JDP001')->first();

        if ($client) {
            $hqJkt = HeadQuarter::where('client_id', $client->id)->where('code', 'HO-JKT')->first();
            $hqSby = HeadQuarter::where('client_id', $client->id)->where('code', 'HO-SBY')->first();
            $hqBdg = HeadQuarter::where('client_id', $client->id)->where('code', 'HO-BDG')->first();

            if ($hqJkt) {
                Merchant::updateOrCreate(
                    ['client_id' => $client->id, 'merchant_code' => 'MER-JKT-001'],
                    [
                        'head_quarter_id' => $hqJkt->id,
                        'merchant_name' => 'Toko Maju Jaya - Kebayoran',
                        'province_id' => $jakarta->id ?? null,
                        'city_id' => $jakartaSelatan->id ?? null,
                        'district_id' => $kebayoranBaru->id ?? null,
                        'sub_district_id' => $kramatPela->id ?? null,
                        'address' => 'Jl. Panglima Polim No. 45, Kebayoran Baru',
                        'postal_code' => '12160',
                        'phone' => '021-7221234',
                        'email' => 'majujaya.kebayoran@example.com',
                        'status' => 'active',
                    ]
                );

                Merchant::updateOrCreate(
                    ['client_id' => $client->id, 'merchant_code' => 'MER-JKT-002'],
                    [
                        'head_quarter_id' => $hqJkt->id,
                        'merchant_name' => 'Warung Sejahtera - Senopati',
                        'province_id' => $jakarta->id ?? null,
                        'city_id' => $jakartaSelatan->id ?? null,
                        'district_id' => $kebayoranBaru->id ?? null,
                        'address' => 'Jl. Senopati No. 12',
                        'postal_code' => '12190',
                        'phone' => '021-5731234',
                        'email' => 'sejahtera.senopati@example.com',
                        'status' => 'active',
                    ]
                );
            }

            if ($hqSby) {
                Merchant::updateOrCreate(
                    ['client_id' => $client->id, 'merchant_code' => 'MER-SBY-001'],
                    [
                        'head_quarter_id' => $hqSby->id,
                        'merchant_name' => 'Toko Berkah Surabaya',
                        'province_id' => $jawaTimur->id ?? null,
                        'city_id' => $surabaya->id ?? null,
                        'address' => 'Jl. Mayjen Sungkono No. 88',
                        'postal_code' => '60225',
                        'phone' => '031-5671234',
                        'email' => 'berkah.surabaya@example.com',
                        'status' => 'active',
                    ]
                );
            }

            if ($hqBdg) {
                Merchant::updateOrCreate(
                    ['client_id' => $client->id, 'merchant_code' => 'MER-BDG-001'],
                    [
                        'head_quarter_id' => $hqBdg->id,
                        'merchant_name' => 'Toko Jaya Bandung',
                        'province_id' => $jawaBarat->id ?? null,
                        'city_id' => $bandung->id ?? null,
                        'address' => 'Jl. Asia Afrika No. 45',
                        'postal_code' => '40111',
                        'phone' => '022-4231000',
                        'email' => 'jaya.bandung@example.com',
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
