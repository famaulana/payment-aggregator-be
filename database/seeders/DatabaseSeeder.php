<?php

namespace Database\Seeders;

use Database\Seeders\ApiKeySeeder;
use Database\Seeders\ClientSeeder;
use Database\Seeders\HeadQuarterSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\MerchantSeeder;
use Database\Seeders\PassportClientSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\SystemOwnerSeeder;
use Database\Seeders\UserSeeder;
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
            HeadQuarterSeeder::class,
            MerchantSeeder::class,
            UserSeeder::class,
            ApiKeySeeder::class,
        ]);
    }
}
