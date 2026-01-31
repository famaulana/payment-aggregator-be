<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\HeadOffice;
use App\Models\Merchant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MerchantSeeder extends Seeder
{
    public function run(): void
    {
        $jakarta = DB::table('provinces')->where('code', '31')->first();
        $jawaBarat = DB::table('provinces')->where('code', '32')->first();
        $jawaTimur = DB::table('provinces')->where('code', '35')->first();
        $jakartaSelatan = DB::table('cities')->where('code', '3171')->first();
        $jakartaPusat = DB::table('cities')->where('code', '3172')->first();
        $surabaya = DB::table('cities')->where('code', '3578')->first();
        $bandung = DB::table('cities')->where('code', '3273')->first();

        $kebayoranBaru = $jakartaSelatan ? DB::table('districts')
            ->where('city_id', $jakartaSelatan->id)
            ->where('name', 'like', '%Kebayoran Baru%')
            ->first() : null;

        $tanahAbang = $jakartaPusat ? DB::table('districts')
            ->where('city_id', $jakartaPusat->id)
            ->where('name', 'like', '%Tanah Abang%')
            ->first() : null;

        $kramatPela = $kebayoranBaru ? DB::table('sub_districts')
            ->where('district_id', $kebayoranBaru->id)
            ->where('name', 'like', '%Kramat Pela%')
            ->first() : null;

        $dpi = Client::where('client_code', 'DPI001')->first();
        $retail = Client::where('client_code', 'RET002')->first();

        if ($dpi) {
            $hoJkt = HeadOffice::where('client_id', $dpi->id)->where('code', 'HO-JKT')->first();
            $hoSby = HeadOffice::where('client_id', $dpi->id)->where('code', 'HO-SBY')->first();

            if ($hoJkt) {
                Merchant::updateOrCreate(
                    ['client_id' => $dpi->id, 'merchant_code' => 'MER-JKT-001'],
                    [
                        'head_office_id' => $hoJkt->id,
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
                    ['client_id' => $dpi->id, 'merchant_code' => 'MER-JKT-002'],
                    [
                        'head_office_id' => $hoJkt->id,
                        'merchant_name' => 'Warung Sejahtera - Tanah Abang',
                        'province_id' => $jakarta->id ?? null,
                        'city_id' => $jakartaPusat->id ?? null,
                        'district_id' => $tanahAbang->id ?? null,
                        'address' => 'Jl. K.H. Mas Mansyur No. 32',
                        'postal_code' => '10220',
                        'phone' => '021-5731234',
                        'email' => 'sejahtera.tanahabang@example.com',
                        'status' => 'active',
                    ]
                );
            }

            if ($hoSby) {
                Merchant::updateOrCreate(
                    ['client_id' => $dpi->id, 'merchant_code' => 'MER-SBY-001'],
                    [
                        'head_office_id' => $hoSby->id,
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
        }

        if ($retail) {
            $hoJktRetail = HeadOffice::where('client_id', $retail->id)->where('code', 'HO-JKT')->first();
            $hoBdg = HeadOffice::where('client_id', $retail->id)->where('code', 'HO-BDG')->first();

            if ($hoJktRetail) {
                Merchant::updateOrCreate(
                    ['client_id' => $retail->id, 'merchant_code' => 'RET-JKT-001'],
                    [
                        'head_office_id' => $hoJktRetail->id,
                        'merchant_name' => 'Retail Store Plaza Indonesia',
                        'province_id' => $jakarta->id ?? null,
                        'city_id' => $jakartaPusat->id ?? null,
                        'address' => 'Plaza Indonesia Level 1, Jl. M.H. Thamrin',
                        'postal_code' => '10350',
                        'phone' => '021-3102000',
                        'email' => 'pi.store@retailnusantara.com',
                        'pos_merchant_id' => 'POS-001-JKT',
                        'status' => 'active',
                    ]
                );
            }

            if ($hoBdg) {
                Merchant::updateOrCreate(
                    ['client_id' => $retail->id, 'merchant_code' => 'RET-BDG-001'],
                    [
                        'head_office_id' => $hoBdg->id,
                        'merchant_name' => 'Retail Store Bandung Indah Plaza',
                        'province_id' => $jawaBarat->id ?? null,
                        'city_id' => $bandung->id ?? null,
                        'address' => 'Bandung Indah Plaza Lt. 2, Jl. Merdeka',
                        'postal_code' => '40111',
                        'phone' => '022-4231000',
                        'email' => 'bip.store@retailnusantara.com',
                        'pos_merchant_id' => 'POS-001-BDG',
                        'status' => 'active',
                    ]
                );
            }
        }
    }
}
