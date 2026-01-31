# Generate Passport Client Secrets
# Run this command to generate new secrets for production

<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

echo "Generating Passport Client Secrets...\n\n";

$clients = DB::table('oauth_clients')->where('name', 'like', '%Password Grant%')->get();

foreach ($clients as $client) {
    $plainSecret = Str::random(40);
    $hashedSecret = password_hash($plainSecret, PASSWORD_BCRYPT);
    
    DB::table('oauth_clients')
        ->where('id', $client->id)
        ->update(['secret' => $hashedSecret]);
    
    $envKey = match($client->name) {
        'Dashboard Password Grant' => 'PASSPORT_DASHBOARD_CLIENT_SECRET',
        'API Server Password Grant' => 'PASSPORT_API_SERVER_CLIENT_SECRET',
        default => null
    };
    
    if ($envKey) {
        echo "{$client->name}\n";
        echo "  Client ID: {$client->id}\n";
        echo "  {$envKey}={$plainSecret}\n\n";
    }
}

echo "Add these to your .env file!\n";
