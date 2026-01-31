<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /**
         * ROLES
         */
        $roles = [
            'system_owner' => 'System Owner',
            'client'       => 'Client',
            'head_office'  => 'Head Office',
            'merchant'     => 'Merchant',
        ];

        foreach ($roles as $name => $displayName) {
            Role::updateOrCreate(
                [
                    'name'       => $name,
                    'guard_name' => 'api',
                ],
                [
                    'name'       => $name,
                    'guard_name' => 'api',
                ]
            );
        }

        /**
         * PERMISSIONS
         */
        $permissions = [
            // Client
            'view_transactions',
            'create_transaction',
            'view_settlements',
            'view_reconciliations',

            // Head Office
            'view_merchant_transactions',
            'view_merchant_settlements',

            // Merchant
            'view_own_transactions',
            'create_own_transaction',

            // System Owner
            'manage_clients',
            'manage_users',
            'manage_api_keys',
            'manage_settlements',
            'manage_reconciliations',
            'manage_floating_funds',
            'view_audit_trails',
            'override_transactions',
            'approve_settlements',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'name'       => $permission,
                    'guard_name' => 'api',
                ],
                [
                    'name'       => $permission,
                    'guard_name' => 'api',
                ]
            );
        }

        /**
         * ASSIGN PERMISSIONS
         */
        Role::where('name', 'system_owner')
            ->first()
            ?->syncPermissions(Permission::where('guard_name', 'api')->get());

        Role::where('name', 'client')
            ->first()
            ?->syncPermissions([
                'view_transactions',
                'create_transaction',
                'view_settlements',
                'view_reconciliations',
            ]);

        Role::where('name', 'head_office')
            ->first()
            ?->syncPermissions([
                'view_merchant_transactions',
                'view_merchant_settlements',
            ]);

        Role::where('name', 'merchant')
            ->first()
            ?->syncPermissions([
                'view_own_transactions',
                'create_own_transaction',
            ]);
    }
}
