<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use Laravel\Passport\Client;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->configurePassport();
        $this->configurePassportTokensCan();
    }

    protected function configurePassport(): void
    {
        // Semua TTL dalam satuan menit
        $dashboardAccessTTL  = (int) config('passport.dashboard_access_token_ttl', 60);   // menit
        $dashboardRefreshTTL = (int) config('passport.dashboard_refresh_token_ttl', 4320); // menit
        $apiAccessTTL        = (int) config('passport.api_access_token_ttl', 60);          // menit

        Passport::tokensExpireIn(new \DateInterval('PT' . $apiAccessTTL . 'M'));
        Passport::refreshTokensExpireIn(new \DateInterval('PT' . $dashboardRefreshTTL . 'M'));
        Passport::personalAccessTokensExpireIn(new \DateInterval('PT' . $dashboardAccessTTL . 'M'));

        // Enable password grant
        Passport::enablePasswordGrant();
    }

    protected function configurePassportTokensCan(): void
    {
        Passport::tokensCan([
            'system-owner' => 'System Owner Access',
            'client' => 'Client Access',
            'head-quarter' => 'Head Quarter Access',
            'merchant' => 'Merchant Access',
            'check-status' => 'Check transaction status',
            'webhook' => 'Webhook access',
        ]);
    }
}
