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
        // FE Dashboard Client - Dengan refresh token (30 hari)
        $dashboardClientId = Str::uuid();
        $dashboardClientSecretPlain = Str::random(40);
        $dashboardClientSecretHashed = Hash::make($dashboardClientSecretPlain);

        DB::table('oauth_clients')->insert([
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
        ]);

        // API Server Client - TANPA refresh token (access token 60 menit)
        $apiServerClientId = Str::uuid();
        $apiServerClientSecretPlain = Str::random(40);
        $apiServerClientSecretHashed = Hash::make($apiServerClientSecretPlain);

        DB::table('oauth_clients')->insert([
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
        ]);

        // Display secrets untuk disalin ke .env
        $this->command->info('=================================================');
        $this->command->info('PASSPORT CLIENTS CREATED SUCCESSFULLY');
        $this->command->info('=================================================');
        $this->command->info("Dashboard Client ID: {$dashboardClientId}");
        $this->command->info("Dashboard Client Secret: {$dashboardClientSecretPlain}");
        $this->command->info('');
        $this->command->info("API Server Client ID: {$apiServerClientId}");
        $this->command->info("API Server Client Secret: {$apiServerClientSecretPlain}");
        $this->command->info('=================================================');
        $this->command->newLine();
        $this->command->warn('IMPORTANT: Update your .env file with these values!');
        $this->command->warn('PASSPORT_DASHBOARD_CLIENT_ID=' . $dashboardClientId);
        $this->command->warn('PASSPORT_DASHBOARD_CLIENT_SECRET=' . $dashboardClientSecretPlain);
        $this->command->warn('PASSPORT_API_SERVER_CLIENT_ID=' . $apiServerClientId);
        $this->command->warn('PASSPORT_API_SERVER_CLIENT_SECRET=' . $apiServerClientSecretPlain);
        $this->command->info('=================================================');

        // Save to temporary file for testing
        file_put_contents(base_path('.env.passport'), http_build_query([
            'PASSPORT_DASHBOARD_CLIENT_ID' => $dashboardClientId,
            'PASSPORT_DASHBOARD_CLIENT_SECRET' => $dashboardClientSecretPlain,
            'PASSPORT_API_SERVER_CLIENT_ID' => $apiServerClientId,
            'PASSPORT_API_SERVER_CLIENT_SECRET' => $apiServerClientSecretPlain,
        ], '', "\n"));

        $this->command->info('Plain secrets saved to .env.passport for testing');
        $this->command->info('=================================================');
    }
}
