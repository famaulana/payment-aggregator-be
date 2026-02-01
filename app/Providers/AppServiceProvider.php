<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Relations\Relation;
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
        // Define morph map for polymorphic relations
        Relation::morphMap([
            'api_key' => \App\Models\ApiKey::class,
            'user' => \App\Models\User::class,
            'client' => \App\Models\Client::class,
            'system_owner' => \App\Models\SystemOwner::class,
            'head_office' => \App\Models\HeadOffice::class,
            'merchant' => \App\Models\Merchant::class,
        ]);
    }
}
