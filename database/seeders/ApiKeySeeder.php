<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ApiKeySeeder extends Seeder
{
    public function run(): void
    {
        $systemOwner = User::where('email', 'system-owner@jdp.co.id')->first();
        $client = Client::where('client_code', 'JDP001')->first();

        if (!$systemOwner || !$client) {
            return;
        }

        ApiKey::updateOrCreate(
            ['client_id' => $client->id, 'key_name' => 'Production API Key'],
            [
                'api_key' => 'pk_prod_jdp_1234567890abcdef',
                'api_key_hashed' => hash('sha256', 'pk_prod_jdp_1234567890abcdef'),
                'api_secret_hashed' => Hash::make('sk_prod_jdp_secretkey1234567890abcdef'),
                'environment' => 'production',
                'status' => 'active',
                // 'ip_whitelist' => '192.168.1.100,203.0.113.0,198.51.100.0',
                'rate_limit_per_minute' => 100,
                'rate_limit_per_hour' => 5000,
                'notes' => 'Production API Key for PT Jago Digital Payment',
                'created_by' => $systemOwner->id,
            ]
        );

        // IP whitelist for development environment - allowing common development IPs
        ApiKey::updateOrCreate(
            ['client_id' => $client->id, 'key_name' => 'Development API Key'],
            [
                'api_key' => 'pk_dev_jdp_abcdef1234567890',
                'api_key_hashed' => hash('sha256', 'pk_dev_jdp_abcdef1234567890'),
                'api_secret_hashed' => Hash::make('sk_dev_jdp_devsecretkey0987654321'),
                'environment' => 'dev',
                'status' => 'active',
                // 'ip_whitelist' => '192.168.1.0/24,10.0.0.0/8',
                'rate_limit_per_minute' => 60,
                'rate_limit_per_hour' => 1000,
                'notes' => 'Development API Key for testing',
                'created_by' => $systemOwner->id,
            ]
        );

        ApiKey::updateOrCreate(
            ['client_id' => $client->id, 'key_name' => 'Staging API Key'],
            [
                'api_key' => 'pk_staging_jdp_9876543210fedcba',
                'api_key_hashed' => hash('sha256', 'pk_staging_jdp_9876543210fedcba'),
                'api_secret_hashed' => Hash::make('sk_staging_jdp_stagingsecretabcdefgh'),
                'environment' => 'staging',
                'status' => 'active',
                // 'ip_whitelist' => '192.0.2.0/24,203.0.113.100',
                'rate_limit_per_minute' => 60,
                'rate_limit_per_hour' => 1000,
                'notes' => 'Staging API Key for testing',
                'created_by' => $systemOwner->id,
            ]
        );
    }
}
