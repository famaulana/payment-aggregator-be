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

        $this->createRoles();
        $this->createPermissions();
        $this->assignPermissionsToRoles();
    }

    private function createRoles(): void
    {
        $roles = [
            ['name' => 'system_owner', 'display_name' => 'System Owner', 'parent_role' => null],
            ['name' => 'system_owner_admin', 'display_name' => 'System Owner Admin', 'parent_role' => 'system_owner'],
            ['name' => 'system_owner_finance', 'display_name' => 'System Owner Finance', 'parent_role' => 'system_owner'],
            ['name' => 'system_owner_support', 'display_name' => 'System Owner Support', 'parent_role' => 'system_owner'],
            
            ['name' => 'client', 'display_name' => 'Client', 'parent_role' => null],
            ['name' => 'client_admin', 'display_name' => 'Client Admin', 'parent_role' => 'client'],
            ['name' => 'client_finance', 'display_name' => 'Client Finance', 'parent_role' => 'client'],
            ['name' => 'client_operations', 'display_name' => 'Client Operations', 'parent_role' => 'client'],
            
            ['name' => 'head_office', 'display_name' => 'Head Office', 'parent_role' => null],
            ['name' => 'head_office_admin', 'display_name' => 'Head Office Admin', 'parent_role' => 'head_office'],
            ['name' => 'head_office_supervisor', 'display_name' => 'Head Office Supervisor', 'parent_role' => 'head_office'],
            
            ['name' => 'merchant', 'display_name' => 'Merchant', 'parent_role' => null],
            ['name' => 'merchant_admin', 'display_name' => 'Merchant Admin', 'parent_role' => 'merchant'],
            ['name' => 'merchant_cashier', 'display_name' => 'Merchant Cashier', 'parent_role' => 'merchant'],
        ];

        foreach ($roles as $roleData) {
            Role::updateOrCreate(
                [
                    'name' => $roleData['name'],
                    'guard_name' => 'api',
                ],
                [
                    'name' => $roleData['name'],
                    'guard_name' => 'api',
                    'display_name' => $roleData['display_name'],
                    'parent_role' => $roleData['parent_role'] ?? null,
                ]
            );
        }
    }

    private function createPermissions(): void
    {
        $permissions = [
            ['name' => 'manage_platform', 'display_name' => 'Manage Platform', 'category' => 'platform'],
            ['name' => 'manage_all_clients', 'display_name' => 'Manage All Clients', 'category' => 'client'],
            ['name' => 'manage_all_users', 'display_name' => 'Manage All Users', 'category' => 'user'],
            ['name' => 'view_all_transactions', 'display_name' => 'View All Transactions', 'category' => 'transaction'],
            ['name' => 'manage_all_settlements', 'display_name' => 'Manage All Settlements', 'category' => 'settlement'],
            ['name' => 'approve_settlements', 'display_name' => 'Approve Settlements', 'category' => 'settlement'],
            ['name' => 'manage_floating_funds', 'display_name' => 'Manage Floating Funds', 'category' => 'finance'],
            ['name' => 'view_audit_trails', 'display_name' => 'View Audit Trails', 'category' => 'audit'],
            ['name' => 'override_transactions', 'display_name' => 'Override Transactions', 'category' => 'transaction'],
            ['name' => 'manage_payment_gateways', 'display_name' => 'Manage Payment Gateways', 'category' => 'payment_gateway'],

            ['name' => 'manage_own_head_offices', 'display_name' => 'Manage Own Head Offices', 'category' => 'head_office'],
            ['name' => 'manage_own_merchants', 'display_name' => 'Manage Own Merchants', 'category' => 'merchant'],
            ['name' => 'manage_own_users', 'display_name' => 'Manage Own Users', 'category' => 'user'],
            ['name' => 'manage_own_api_keys', 'display_name' => 'Manage Own API Keys', 'category' => 'api_key'],
            ['name' => 'view_own_transactions', 'display_name' => 'View Own Transactions', 'category' => 'transaction'],
            ['name' => 'view_own_settlements', 'display_name' => 'View Own Settlements', 'category' => 'settlement'],
            ['name' => 'view_own_reports', 'display_name' => 'View Own Reports', 'category' => 'report'],

            ['name' => 'manage_own_merchant_users', 'display_name' => 'Manage Own Merchant Users', 'category' => 'user'],
            ['name' => 'view_merchant_transactions', 'display_name' => 'View Merchant Transactions', 'category' => 'transaction'],
            ['name' => 'view_merchant_settlements', 'display_name' => 'View Merchant Settlements', 'category' => 'settlement'],
            ['name' => 'view_merchant_reports', 'display_name' => 'View Merchant Reports', 'category' => 'report'],

            ['name' => 'process_payments', 'display_name' => 'Process Payments', 'category' => 'payment'],
            ['name' => 'view_merchant_data', 'display_name' => 'View Merchant Data', 'category' => 'merchant'],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                [
                    'name' => $permission['name'],
                    'guard_name' => 'api',
                ],
                [
                    'name' => $permission['name'],
                    'guard_name' => 'api',
                    'display_name' => $permission['display_name'] ?? null,
                    'category' => $permission['category'] ?? null,
                ]
            );
        }
    }

    private function assignPermissionsToRoles(): void
    {
        $systemOwner = Role::where('name', 'system_owner')->first();
        $systemOwner?->syncPermissions(Permission::all());

        $systemOwnerAdmin = Role::where('name', 'system_owner_admin')->first();
        $systemOwnerAdmin?->syncPermissions(
            Permission::where('category', '!=', 'finance')->get()
        );

        $systemOwnerFinance = Role::where('name', 'system_owner_finance')->first();
        $systemOwnerFinance?->syncPermissions(
            Permission::whereIn('category', ['finance', 'settlement'])->get()
        );

        $client = Role::where('name', 'client')->first();
        $client?->syncPermissions([
            'manage_own_head_offices',
            'manage_own_merchants',
            'manage_own_users',
            'manage_own_api_keys',
            'view_own_transactions',
            'view_own_settlements',
            'view_own_reports',
        ]);

        $clientAdmin = Role::where('name', 'client_admin')->first();
        $clientAdmin?->syncPermissions($client?->permissions ?? collect());

        $clientFinance = Role::where('name', 'client_finance')->first();
        $clientFinance?->syncPermissions([
            'view_own_transactions',
            'view_own_settlements',
            'view_own_reports',
        ]);

        $headOffice = Role::where('name', 'head_office')->first();
        $headOffice?->syncPermissions([
            'manage_own_merchant_users',
            'view_merchant_transactions',
            'view_merchant_settlements',
            'view_merchant_reports',
        ]);

        $headOfficeAdmin = Role::where('name', 'head_office_admin')->first();
        $headOfficeAdmin?->syncPermissions($headOffice?->permissions ?? collect());

        $merchant = Role::where('name', 'merchant')->first();
        $merchant?->syncPermissions([
            'process_payments',
            'view_merchant_data',
        ]);

        $merchantAdmin = Role::where('name', 'merchant_admin')->first();
        $merchantAdmin?->syncPermissions($merchant?->permissions ?? collect());
    }
}
