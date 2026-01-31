<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            PassportClientSeeder::class,
            LocationSeeder::class,
            SystemOwnerSeeder::class,
            ClientSeeder::class,
            HeadOfficeSeeder::class,
            MerchantSeeder::class,
            UserSeeder::class,
            ApiKeySeeder::class,
        ]);
    }
}
