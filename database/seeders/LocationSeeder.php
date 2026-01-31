<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LocationSeeder extends Seeder
{
    private $csvPath = 'private/csv/';

    public function run(): void
    {
        $this->importProvinces();
        $this->importCities();
        $this->importDistricts();
        $this->importSubDistricts();
    }

    private function importProvinces(): void
    {
        $csvFile = storage_path('app/' . $this->csvPath . 'provinsi.csv');
        if (!file_exists($csvFile)) return;

        $file = fopen($csvFile, 'r');
        fgetcsv($file, 0, ';');

        $data = [];
        while (($row = fgetcsv($file, 0, ';')) !== false) {
            if (count($row) >= 2) {
                $data[] = [
                    'code' => $row[0],
                    'name' => $row[1],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($data) >= 100) {
                    DB::table('provinces')->insertOrIgnore($data);
                    $data = [];
                }
            }
        }

        if (count($data) > 0) {
            DB::table('provinces')->insertOrIgnore($data);
        }

        fclose($file);
    }

    private function importCities(): void
    {
        $csvFile = storage_path('app/' . $this->csvPath . 'kabupaten.csv');
        if (!file_exists($csvFile)) return;

        $file = fopen($csvFile, 'r');
        fgetcsv($file, 0, ';');

        $provinceMap = DB::table('provinces')->pluck('id', 'code')->toArray();
        $data = [];

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            if (count($row) >= 3 && isset($provinceMap[$row[2]])) {
                $name = $row[1];
                $type = stripos($name, 'Kota ') === 0 ? 'kota' : 'kabupaten';

                $data[] = [
                    'province_id' => $provinceMap[$row[2]],
                    'code' => $row[0],
                    'name' => $name,
                    'type' => $type,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($data) >= 100) {
                    DB::table('cities')->insertOrIgnore($data);
                    $data = [];
                }
            }
        }

        if (count($data) > 0) {
            DB::table('cities')->insertOrIgnore($data);
        }

        fclose($file);
    }

    private function importDistricts(): void
    {
        $csvFile = storage_path('app/' . $this->csvPath . 'kecamatan.csv');
        if (!file_exists($csvFile)) return;

        $file = fopen($csvFile, 'r');
        fgetcsv($file, 0, ';');

        $cityMap = DB::table('cities')->pluck('id', 'code')->toArray();
        $data = [];

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            if (count($row) >= 3 && isset($cityMap[$row[0]])) {
                $data[] = [
                    'city_id' => $cityMap[$row[0]],
                    'code' => $row[1],
                    'name' => $row[2],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($data) >= 500) {
                    DB::table('districts')->insertOrIgnore($data);
                    $data = [];
                }
            }
        }

        if (count($data) > 0) {
            DB::table('districts')->insertOrIgnore($data);
        }

        fclose($file);
    }

    private function importSubDistricts(): void
    {
        $csvFile = storage_path('app/' . $this->csvPath . 'desa.csv');
        if (!file_exists($csvFile)) return;

        $file = fopen($csvFile, 'r');
        fgetcsv($file, 0, ';');

        $districtMap = DB::table('districts')->pluck('id', 'code')->toArray();
        $data = [];

        while (($row = fgetcsv($file, 0, ';')) !== false) {
            if (count($row) >= 3 && isset($districtMap[$row[0]])) {
                $data[] = [
                    'district_id' => $districtMap[$row[0]],
                    'code' => $row[1],
                    'name' => $row[2],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                if (count($data) >= 1000) {
                    DB::table('sub_districts')->insertOrIgnore($data);
                    $data = [];
                }
            }
        }

        if (count($data) > 0) {
            DB::table('sub_districts')->insertOrIgnore($data);
        }

        fclose($file);
    }
}
