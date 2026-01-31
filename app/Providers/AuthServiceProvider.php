<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\RolePolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
use Spatie\Permission\Models\Role;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Role::class => RolePolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

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

        Gate::define('assign-single-role', function (User $user, User $targetUser) {
            if (!$user->hasRole('system_owner')) {
                return false;
            }

            return $targetUser->roles()->count() === 0;
        });

        Gate::define('change-role', function (User $user, User $targetUser) {
            if (!$user->hasRole('system_owner')) {
                return false;
            }

            return true;
        });

        Gate::before(function ($user, $ability) {
            if ($user->hasRole('system_owner')) {
                return true;
            }
        });

        Gate::after(function ($user, $ability, $result, $arguments) {
            if ($user && $user->roles()->count() > 1) {
                // Log ke audit trail
                \Log::warning('User memiliki multiple roles', [
                    'user_id' => $user->id,
                    'roles' => $user->roles->pluck('name'),
                ]);

                $firstRole = $user->roles()->first();
                $user->roles()->sync([$firstRole->id]);
            }
            return $result;
        });
    }
}
