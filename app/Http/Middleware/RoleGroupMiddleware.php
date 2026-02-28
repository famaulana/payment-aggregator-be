<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;

class RoleGroupMiddleware
{
    /**
     * Role group mappings
     * Each group contains the base role and all its child roles
     */
    private array $roleGroups = [
        'is_system_owner' => ['system_owner', 'system_owner_admin', 'system_owner_finance', 'system_owner_support'],
        'is_client' => ['client', 'client_admin', 'client_finance', 'client_operations'],
        'is_head_quarter' => ['head_quarter'],
        'is_merchant' => ['merchant'],
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $roleGroup  The role group to check (e.g., 'is_system_owner', 'is_client')
     */
    public function handle(Request $request, Closure $next, string $roleGroup): Response
    {
        if (Auth::guest()) {
            return response()->json([
                'response_code' => \App\Enums\ResponseCode::UNAUTHORIZED->value,
                'response_message' => __('messages.unauthorized')
            ], \App\Enums\ResponseCode::UNAUTHORIZED->getHttpStatusCode());
        }

        $user = Auth::user();

        // Parse role groups by pipe separator (for multiple groups)
        $roleGroups = explode('|', $roleGroup);

        // Check if user belongs to any of the required role groups
        foreach ($roleGroups as $group) {
            if ($this->userBelongsToRoleGroup($user, $group)) {
                return $next($request);
            }
        }

        return response()->json([
            'response_code' => \App\Enums\ResponseCode::FORBIDDEN->value,
            'response_message' => __('messages.forbidden')
        ], \App\Enums\ResponseCode::FORBIDDEN->getHttpStatusCode());
    }

    /**
     * Check if user belongs to a specific role group
     *
     * @param  \App\Models\User  $user
     * @param  string  $roleGroup
     * @return bool
     */
    private function userBelongsToRoleGroup($user, string $roleGroup): bool
    {
        // Get roles for the specified group
        $groupRoles = $this->getRolesForGroup($roleGroup);

        if (empty($groupRoles)) {
            return false;
        }

        // Check if user has any of the roles in the group
        foreach ($groupRoles as $roleName) {
            if ($user->hasRole($roleName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all roles for a specific role group
     * Dynamically retrieves roles including child roles based on parent_role field
     *
     * @param  string  $roleGroup
     * @return array
     */
    private function getRolesForGroup(string $roleGroup): array
    {
        // If the role group is defined in the mapping, return it
        if (isset($this->roleGroups[$roleGroup])) {
            return $this->roleGroups[$roleGroup];
        }

        // Otherwise, try to dynamically build the group based on parent_role
        $baseRoleName = str_replace('is_', '', $roleGroup);

        // Get the base role
        $baseRole = Role::where('name', $baseRoleName)->first();

        if (!$baseRole) {
            return [];
        }

        // Get all child roles
        $childRoles = Role::where('parent_role', $baseRoleName)
            ->pluck('name')
            ->toArray();

        // Combine base role with child roles
        return array_merge([$baseRoleName], $childRoles);
    }

    /**
     * Refresh role groups from database
     * Call this if roles are updated dynamically
     *
     * @return void
     */
    public function refreshRoleGroups(): void
    {
        // Get all base roles (roles without parent_role)
        $baseRoles = Role::whereNull('parent_role')->get();

        foreach ($baseRoles as $baseRole) {
            $groupKey = 'is_' . $baseRole->name;

            // Get all child roles
            $childRoles = Role::where('parent_role', $baseRole->name)
                ->pluck('name')
                ->toArray();

            // Build the group array
            $this->roleGroups[$groupKey] = array_merge([$baseRole->name], $childRoles);
        }
    }
}
