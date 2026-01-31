<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\HeadOffice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HeadOfficeSeeder extends Seeder
{
    public function run(): void
    {
        $jakarta = DB::table('provinces')->where('code', '31')->first();
        $jawaBarat = DB::table('provinces')->where('code', '32')->first();
        $jawaTimur = DB::table('provinces')->where('code', '35')->first();
        $jakartaSelatan = DB::table('cities')->where('code', '3171')->first();
        $surabaya = DB::table('cities')->where('code', '3578')->first();
        $bandung = DB::table('cities')->where('code', '3273')->first();

        $kebayoranBaru = $jakartaSelatan ? DB::table('districts')
            ->where('city_id', $jakartaSelatan->id)
            ->where('name', 'like', '%Kebayoran Baru%')
            ->first() : null;

        $gunung = $kebayoranBaru ? DB::table('sub_districts')
            ->where('district_id', $kebayoranBaru->id)
            ->where('name', 'like', '%Gunung%')
            ->first() : null;

        $client = Client::where('client_code', 'JDP001')->first();

        if ($client) {
            HeadOffice::updateOrCreate(
                ['client_id' => $client->id, 'code' => 'HO-JKT'],
                [
                    'name' => 'Head Office Jakarta',
                    'province_id' => $jakarta->id ?? null,
                    'city_id' => $jakartaSelatan->id ?? null,
                    'district_id' => $kebayoranBaru->id ?? null,
                    'sub_district_id' => $gunung->id ?? null,
                    'address' => 'Jl. Jend. Sudirman Kav. 52-53, SCBD',
                    'postal_code' => '12190',
                    'phone' => '021-5551234',
                    'email' => 'jakarta@jdp.co.id',
                    'status' => 'active',
                ]
            );

            HeadOffice::updateOrCreate(
                ['client_id' => $client->id, 'code' => 'HO-SBY'],
                [
                    'name' => 'Head Office Surabaya',
                    'province_id' => $jawaTimur->id ?? null,
                    'city_id' => $surabaya->id ?? null,
                    'address' => 'Jl. HR. Muhammad No. 123, Surabaya',
                    'postal_code' => '60241',
                    'phone' => '031-5551234',
                    'email' => 'surabaya@jdp.co.id',
                    'status' => 'active',
                ]
            );

            HeadOffice::updateOrCreate(
                ['client_id' => $client->id, 'code' => 'HO-BDG'],
                [
                    'name' => 'Head Office Bandung',
                    'province_id' => $jawaBarat->id ?? null,
                    'city_id' => $bandung->id ?? null,
                    'address' => 'Jl. Asia Afrika No. 8, Bandung',
                    'postal_code' => '40111',
                    'phone' => '022-4201234',
                    'email' => 'bandung@jdp.co.id',
                    'status' => 'active',
                ]
            );
        }
    }
}
