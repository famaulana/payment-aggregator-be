<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PassportClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $personalAccessClientId = config('passport.personal_access_client.id')
            ?? (string) Str::uuid();

        DB::table('oauth_clients')->updateOrInsert(
            ['id' => $personalAccessClientId],
            [
                'id' => $personalAccessClientId,
                'owner_type' => null,
                'owner_id' => null,
                'name' => config('app.name') . ' Personal Access Client',
                'secret' => Hash::make(config('passport.personal_access_client.secret'))
                    ?? Str::random(40),
                'provider' => null,
                'redirect_uris' => json_encode([config('app.url')]),
                'grant_types' => json_encode(['personal_access']),
                'revoked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        DB::table('oauth_personal_access_clients')->updateOrInsert(
            ['client_id' => $personalAccessClientId],
            [
                'client_id' => $personalAccessClientId,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $passwordClientId = config('passport.password_client.id')
            ?? (string) Str::uuid();

        DB::table('oauth_clients')->updateOrInsert(
            ['id' => $passwordClientId],
            [
                'id' => $passwordClientId,
                'owner_type' => null,
                'owner_id' => null,
                'name' => config('app.name') . ' Password Grant Client',
                'secret' => Hash::make(config('passport.password_client.secret'))
                    ?? Str::random(40),
                'provider' => null,
                'redirect_uris' => json_encode([config('app.url')]),
                'grant_types' => json_encode(['password', 'refresh_token']),
                'revoked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
