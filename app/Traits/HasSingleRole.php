<?php

namespace App\Traits;

trait HasSingleRole
{
    public static function bootHasSingleRole()
    {
        static::saved(function ($user) {
            if ($user->roles()->count() > 1) {
                $firstRole = $user->roles()->first();
                $user->roles()->sync([$firstRole->id]);
            }
        });
    }

    public function assignSingleRole($role): static
    {
        if (is_string($role)) {
            $role = \Spatie\Permission\Models\Role::findByName($role, $this->getDefaultGuardName());
        }

        $this->roles()->sync([$role->id]);

        $this->forgetCachedPermissions();

        return $this;
    }

    public function getSingleRoleAttribute()
    {
        return $this->roles()->first();
    }
}
