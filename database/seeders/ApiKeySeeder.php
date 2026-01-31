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
        $systemOwner = User::where('email', 'system-owner@pg-lit.test')->first();
        $dpi = Client::where('client_code', 'DPI001')->first();
        $retail = Client::where('client_code', 'RET002')->first();
        $fnb = Client::where('client_code', 'FNB003')->first();

        if ($dpi && $systemOwner) {
            $apiKey1 = 'dpi_prod_1234567890abcdef1234567890abcdef';
            $apiSecret1 = 'secret_dpi_prod_1234567890abcdef1234567890abcdef1234567890abcdef';

            ApiKey::updateOrCreate(
                ['client_id' => $dpi->id, 'key_name' => 'DPI Production Key'],
                [
                    'api_key' => $apiKey1,
                    'api_key_hashed' => hash('sha256', $apiKey1),
                    'api_secret_hashed' => Hash::make($apiSecret1),
                    'environment' => 'production',
                    'status' => 'active',
                    // 'ip_whitelist' => json_encode(['103.10.10.10', '103.10.10.11']),
                    'rate_limit_per_minute' => 100,
                    'rate_limit_per_hour' => 5000,
                    'notes' => 'Production API Key for PT Digital Payment Indonesia',
                    'created_by' => $systemOwner->id,
                ]
            );

            $apiKey2 = 'dpi_dev_abcdef1234567890abcdef1234567890';
            $apiSecret2 = 'secret_dpi_dev_abcdef1234567890abcdef1234567890abcdef1234567890';

            ApiKey::updateOrCreate(
                ['client_id' => $dpi->id, 'key_name' => 'DPI Development Key'],
                [
                    'api_key' => $apiKey2,
                    'api_key_hashed' => hash('sha256', $apiKey2),
                    'api_secret_hashed' => Hash::make($apiSecret2),
                    'environment' => 'dev',
                    'status' => 'active',
                    'ip_whitelist' => null,
                    'rate_limit_per_minute' => 60,
                    'rate_limit_per_hour' => 1000,
                    'notes' => 'Development API Key for testing',
                    'created_by' => $systemOwner->id,
                ]
            );
        }

        if ($retail && $systemOwner) {
            $apiKey3 = 'ret_prod_fedcba0987654321fedcba0987654321';
            $apiSecret3 = 'secret_ret_prod_fedcba0987654321fedcba0987654321fedcba0987654321';

            ApiKey::updateOrCreate(
                ['client_id' => $retail->id, 'key_name' => 'Retail Production Key'],
                [
                    'api_key' => $apiKey3,
                    'api_key_hashed' => hash('sha256', $apiKey3),
                    'api_secret_hashed' => Hash::make($apiSecret3),
                    'environment' => 'production',
                    'status' => 'active',
                    'ip_whitelist' => json_encode(['202.150.10.10', '202.150.10.11']),
                    'rate_limit_per_minute' => 120,
                    'rate_limit_per_hour' => 6000,
                    'notes' => 'Production API Key for PT Retail Nusantara',
                    'created_by' => $systemOwner->id,
                ]
            );
        }

        if ($fnb && $systemOwner) {
            $apiKey4 = 'fnb_stage_abc123def456abc123def456abc12';
            $apiSecret4 = 'secret_fnb_stage_abc123def456abc123def456abc123def456abc123def456';

            ApiKey::updateOrCreate(
                ['client_id' => $fnb->id, 'key_name' => 'FNB Staging Key'],
                [
                    'api_key' => $apiKey4,
                    'api_key_hashed' => hash('sha256', $apiKey4),
                    'api_secret_hashed' => Hash::make($apiSecret4),
                    'environment' => 'staging',
                    'status' => 'active',
                    'ip_whitelist' => null,
                    'rate_limit_per_minute' => 60,
                    'rate_limit_per_hour' => 1000,
                    'notes' => 'Staging API Key for PT Food Berkah (pending KYB)',
                    'created_by' => $systemOwner->id,
                ]
            );
        }
    }
}
