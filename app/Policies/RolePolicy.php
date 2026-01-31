<?php

namespace App\Policies;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Auth\Access\HandlesAuthorization;

class RolePolicy
{
    use HandlesAuthorization;

    /**
     * Determine if the user can assign multiple roles to a user
     */
    public function assignMultipleRoles(User $user, User $targetUser): bool
    {
        return $user->hasRole('system_owner');
    }

    /**
     * Determine if a user can have multiple roles
     */
    public function hasMultipleRoles(User $user): bool
    {
        return false;
    }

    /**
     * Determine if the user can assign this role to target user
     */
    public function assignRole(User $user, Role $role, User $targetUser): bool
    {
        if (!$user->hasRole('system_owner')) {
            return false;
        }

        if ($targetUser->roles()->count() > 0) {
            $targetUser->roles()->detach();
        }

        return true;
    }

    /**
     * Determine if the user can remove role from target user
     */
    public function removeRole(User $user, Role $role, User $targetUser): bool
    {
        return $user->hasRole('system_owner');
    }
}
