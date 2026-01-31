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
        // Default TTL for all clients
        $accessTokenTTL = config('passport.access_token_ttl', 60);
        $refreshTokenTTL = config('passport.refresh_token_ttl', 30);
        $patTokenTTL = config('passport.pat_token_ttl', 90);

        Passport::tokensExpireIn(new \DateInterval('PT'.$accessTokenTTL.'M'));
        Passport::refreshTokensExpireIn(new \DateInterval('P'.$refreshTokenTTL.'D'));
        Passport::personalAccessTokensExpireIn(new \DateInterval('P'.$patTokenTTL.'D'));

        // Enable password grant
        Passport::enablePasswordGrant();
    }

    protected function configurePassportTokensCan(): void
    {
        Passport::tokensCan([
            'system-owner' => 'System Owner Access',
            'client' => 'Client Access',
            'head-office' => 'Head Office Access',
            'merchant' => 'Merchant Access',
            'check-status' => 'Check transaction status',
            'webhook' => 'Webhook access',
        ]);
    }
}

