<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Passport::loadKeysFrom(storage_path('oauth'));
        Passport::ignoreRoutes();
        Passport::enablePasswordGrant();
        Passport::tokensExpireIn(
            now()->addMinutes((int) config('passport.access_token_ttl'))
        );
        Passport::refreshTokensExpireIn(
            now()->addDays((int) config('passport.refresh_token_ttl'))
        );
        Passport::personalAccessTokensExpireIn(
            now()->addDays((int) config('passport.personal_access_token_ttl'))
        );
    }
}
