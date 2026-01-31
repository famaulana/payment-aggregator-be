<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PassportClientSeeder extends Seeder
{
    public function run(): void
    {
        $this->createPersonalAccessClient();
        $this->createPasswordClients();
    }

    private function createPersonalAccessClient(): void
    {
        $clientId = config('passport.personal_access_client.id') ?? Str::uuid();
        $clientSecret = config('passport.personal_access_client.secret') ?? Str::random(40);

        DB::table('oauth_clients')->updateOrInsert(
            ['id' => $clientId],
            [
                'id' => $clientId,
                'owner_type' => null,
                'owner_id' => null,
                'name' => config('app.name') . ' Personal Access Client',
                'secret' => Hash::make($clientSecret),
                'provider' => null,
                'redirect_uris' => json_encode([config('app.url')]),
                'grant_types' => json_encode(['personal_access']),
                'revoked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('oauth_personal_access_clients')->updateOrInsert(
            ['client_id' => $clientId],
            [
                'client_id' => $clientId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    private function createPasswordClients(): void
    {
        $dashboardClientId = config('services.passport.dashboard_client_id') ?? Str::uuid();
        $dashboardClientSecretPlain = config('services.passport.dashboard_client_secret') ?? Str::random(40);
        $dashboardClientSecretHashed = Hash::make($dashboardClientSecretPlain);

        DB::table('oauth_clients')->updateOrInsert(
            ['id' => $dashboardClientId],
            [
                'id' => $dashboardClientId,
                'owner_type' => null,
                'owner_id' => null,
                'name' => 'Dashboard Password Grant',
                'secret' => $dashboardClientSecretHashed,
                'provider' => 'users',
                'redirect_uris' => json_encode([config('app.url')]),
                'grant_types' => json_encode(['password', 'refresh_token']),
                'revoked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $apiServerClientId = config('services.passport.api_server_client_id') ?? Str::uuid();
        $apiServerClientSecretPlain = config('services.passport.api_server_client_secret') ?? Str::random(40);
        $apiServerClientSecretHashed = Hash::make($apiServerClientSecretPlain);

        DB::table('oauth_clients')->updateOrInsert(
            ['id' => $apiServerClientId],
            [
                'id' => $apiServerClientId,
                'owner_type' => null,
                'owner_id' => null,
                'name' => 'API Server Password Grant',
                'secret' => $apiServerClientSecretHashed,
                'provider' => 'users',
                'redirect_uris' => json_encode([config('app.url')]),
                'grant_types' => json_encode(['password']),
                'revoked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->updateEnvFile([
            'PASSPORT_DASHBOARD_CLIENT_ID' => $dashboardClientId,
            'PASSPORT_DASHBOARD_CLIENT_SECRET' => $dashboardClientSecretPlain,
            'PASSPORT_API_SERVER_CLIENT_ID' => $apiServerClientId,
            'PASSPORT_API_SERVER_CLIENT_SECRET' => $apiServerClientSecretPlain,
        ]);
    }

    private function updateEnvFile(array $data): void
    {
        $envFile = base_path('.env');
        $envContent = file_get_contents($envFile);

        foreach ($data as $key => $value) {
            $keyPattern = preg_quote($key, '/');
            
            if (preg_match("/^{$keyPattern}=/m", $envContent)) {
                $envContent = preg_replace(
                    "/^{$keyPattern}=.*/m",
                    "{$key}={$value}",
                    $envContent
                );
            } else {
                $envContent .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envFile, $envContent);
    }
}
