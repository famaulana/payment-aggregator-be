<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Passport\Client;

class PassportSecretService
{
    public function generatePassportSecrets(): array
    {
        $clients = Client::all();
        $secrets = [];

        foreach ($clients as $client) {
            $plainSecret = Str::random(40);
            $hashedSecret = password_hash($plainSecret, PASSWORD_BCRYPT);

            DB::table('oauth_clients')
                ->where('id', $client->id)
                ->update(['secret' => $hashedSecret]);

            $secrets[$client->name] = [
                'id' => $client->id,
                'secret' => $plainSecret,
                'env_key' => $this->getEnvKey($client->name),
            ];
        }

        return $secrets;
    }

    public function getEnvKey(string $clientName): string
    {
        return match($clientName) {
            'Dashboard Password Grant' => 'PASSPORT_DASHBOARD_CLIENT_SECRET',
            'API Server Password Grant' => 'PASSPORT_API_SERVER_CLIENT_SECRET',
            'Personal Access Client' => 'PASSPORT_PERSONAL_ACCESS_CLIENT_SECRET',
            default => 'PASSPORT_' . strtoupper(str_replace(' ', '_', $clientName)) . '_SECRET',
        };
    }

    public function generateEnvContent(array $secrets): string
    {
        $envContent = "# Passport Client Secrets\n";
        $envContent .= "# Generated on " . now()->toDateTimeString() . "\n\n";

        foreach ($secrets as $clientName => $config) {
            $envContent .= "# {$clientName}\n";
            $envContent .= "{$config['env_key']}={$config['secret']}\n";
            $envContent .= "{$config['env_key']}_ID={$config['id']}\n\n";
        }

        return $envContent;
    }
}
