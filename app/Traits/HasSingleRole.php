<?php

namespace App\Traits;

trait HasSingleRole
{
    /**
     * Ensure user only has one role
     */
    public static function bootHasSingleRole()
    {
        static::saved(function ($user) {
            if ($user->roles()->count() > 1) {
                // Keep only the first role
                $firstRole = $user->roles()->first();
                $user->roles()->sync([$firstRole->id]);
            }
        });
    }

    /**
     * Assign a role to the user (replace existing)
     */
    public function assignSingleRole($role): static
    {
        if (is_string($role)) {
            $role = \Spatie\Permission\Models\Role::findByName($role, $this->getDefaultGuardName());
        }

        $this->roles()->sync([$role->id]);

        $this->forgetCachedPermissions();

        return $this;
    }

    /**
     * Get the user's role
     */
    public function getRoleAttribute()
    {
        return $this->roles()->first();
    }

    /**
     * Get the default guard name
     */
    protected function getDefaultGuardName(): string
    {
        return config('auth.defaults.guard', 'web');
    }
}
