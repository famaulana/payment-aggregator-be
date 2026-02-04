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

            ['name' => 'merchant', 'display_name' => 'Merchant', 'parent_role' => null],
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

            ['name' => 'view_all_transactions_national', 'display_name' => 'View All Transactions National', 'category' => 'transaction'],
            ['name' => 'view_regional_transactions', 'display_name' => 'View Regional Transactions', 'category' => 'transaction'],
            ['name' => 'view_own_merchant_transactions', 'display_name' => 'View Own Merchant Transactions', 'category' => 'transaction'],
            ['name' => 'view_merchant_transactions', 'display_name' => 'View Merchant Transactions', 'category' => 'transaction'],
            ['name' => 'filter_by_date', 'display_name' => 'Filter By Date', 'category' => 'transaction'],
            ['name' => 'filter_by_payment_method', 'display_name' => 'Filter By Payment Method', 'category' => 'transaction'],
            ['name' => 'filter_by_payment_gateway', 'display_name' => 'Filter By Payment Gateway', 'category' => 'transaction'],
            ['name' => 'filter_by_merchant', 'display_name' => 'Filter By Merchant', 'category' => 'transaction'],
            ['name' => 'filter_by_regional', 'display_name' => 'Filter By Regional', 'category' => 'transaction'],
            ['name' => 'filter_by_status', 'display_name' => 'Filter By Status', 'category' => 'transaction'],
            ['name' => 'override_transaction_status', 'display_name' => 'Override Transaction Status', 'category' => 'transaction'],
            ['name' => 'view_triple_reference_id', 'display_name' => 'View Triple Reference ID', 'category' => 'transaction'],
            ['name' => 'view_pg_and_application_fee', 'display_name' => 'View PG And Application Fee', 'category' => 'transaction'],

            ['name' => 'request_settlement', 'display_name' => 'Request Settlement', 'category' => 'settlement'],
            ['name' => 'view_all_settlement_requests', 'display_name' => 'View All Settlement Requests', 'category' => 'settlement'],
            ['name' => 'approve_settlement_request', 'display_name' => 'Approve Settlement Request', 'category' => 'settlement'],
            ['name' => 'reject_settlement_request', 'display_name' => 'Reject Settlement Request', 'category' => 'settlement'],
            ['name' => 'view_settlement_history', 'display_name' => 'View Settlement History', 'category' => 'settlement'],
            ['name' => 'manage_all_settlements', 'display_name' => 'Manage All Settlements', 'category' => 'settlement'],
            ['name' => 'approve_settlements', 'display_name' => 'Approve Settlements', 'category' => 'settlement'],
            ['name' => 'view_own_settlements', 'display_name' => 'View Own Settlements', 'category' => 'settlement'],
            ['name' => 'view_merchant_settlements', 'display_name' => 'View Merchant Settlements', 'category' => 'settlement'],

            ['name' => 'view_available_balance', 'display_name' => 'View Available Balance', 'category' => 'finance'],
            ['name' => 'view_pending_balance', 'display_name' => 'View Pending Balance', 'category' => 'finance'],
            ['name' => 'view_minus_balance', 'display_name' => 'View Minus Balance', 'category' => 'finance'],
            ['name' => 'view_balance_history', 'display_name' => 'View Balance History', 'category' => 'finance'],
            ['name' => 'manage_floating_funds', 'display_name' => 'Manage Floating Funds', 'category' => 'finance'],
            ['name' => 'view_floating_fund_summary', 'display_name' => 'View Floating Fund Summary', 'category' => 'finance'],
            ['name' => 'validate_floating_fund', 'display_name' => 'Validate Floating Fund', 'category' => 'finance'],
            ['name' => 'view_settlement_difference', 'display_name' => 'View Settlement Difference', 'category' => 'finance'],
            ['name' => 'view_mdr_configuration', 'display_name' => 'View MDR Configuration', 'category' => 'finance'],
            ['name' => 'set_application_fee', 'display_name' => 'Set Application Fee', 'category' => 'finance'],
            ['name' => 'view_mdr_change_history', 'display_name' => 'View MDR Change History', 'category' => 'finance'],
            ['name' => 'perform_matching_data', 'display_name' => 'Perform Matching Data', 'category' => 'finance'],
            ['name' => 'view_mismatch_data', 'display_name' => 'View Mismatch Data', 'category' => 'finance'],
            ['name' => 'view_client_debt', 'display_name' => 'View Client Debt', 'category' => 'finance'],
            ['name' => 'adjust_settlement_difference', 'display_name' => 'Adjust Settlement Difference', 'category' => 'finance'],

            ['name' => 'view_audit_trails', 'display_name' => 'View Audit Trails', 'category' => 'audit'],
            ['name' => 'view_internal_activity_logs', 'display_name' => 'View Internal Activity Logs', 'category' => 'audit'],
            ['name' => 'filter_internal_logs', 'display_name' => 'Filter Internal Logs', 'category' => 'audit'],
            ['name' => 'view_external_activity_logs', 'display_name' => 'View External Activity Logs', 'category' => 'audit'],
            ['name' => 'filter_external_logs', 'display_name' => 'Filter External Logs', 'category' => 'audit'],
            ['name' => 'view_masked_payload', 'display_name' => 'View Masked Payload', 'category' => 'audit'],

            ['name' => 'manage_payment_gateways', 'display_name' => 'Manage Payment Gateways', 'category' => 'payment_gateway'],
            ['name' => 'view_payment_gateways', 'display_name' => 'View Payment Gateways', 'category' => 'payment_gateway'],
            ['name' => 'view_payment_methods', 'display_name' => 'View Payment Methods', 'category' => 'payment_gateway'],
            ['name' => 'configure_pg_method_mapping', 'display_name' => 'Configure PG Method Mapping', 'category' => 'payment_gateway'],
            ['name' => 'view_pg_fee', 'display_name' => 'View PG Fee', 'category' => 'payment_gateway'],

            ['name' => 'manage_own_head_offices', 'display_name' => 'Manage Own Head Offices', 'category' => 'head_office'],
            ['name' => 'view_headquarter_accounts', 'display_name' => 'View Headquarter Accounts', 'category' => 'head_office'],
            ['name' => 'add_headquarter_account', 'display_name' => 'Add Headquarter Account', 'category' => 'head_office'],
            ['name' => 'update_headquarter_account', 'display_name' => 'Update Headquarter Account', 'category' => 'head_office'],
            ['name' => 'activate_headquarter_account', 'display_name' => 'Activate Headquarter Account', 'category' => 'head_office'],
            ['name' => 'deactivate_headquarter_account', 'display_name' => 'Deactivate Headquarter Account', 'category' => 'head_office'],

            ['name' => 'manage_own_merchants', 'display_name' => 'Manage Own Merchants', 'category' => 'merchant'],
            ['name' => 'view_merchant_list', 'display_name' => 'View Merchant List', 'category' => 'merchant'],
            ['name' => 'add_merchant', 'display_name' => 'Add Merchant', 'category' => 'merchant'],
            ['name' => 'update_merchant_status', 'display_name' => 'Update Merchant Status', 'category' => 'merchant'],
            ['name' => 'activate_merchant', 'display_name' => 'Activate Merchant', 'category' => 'merchant'],
            ['name' => 'deactivate_merchant', 'display_name' => 'Deactivate Merchant', 'category' => 'merchant'],
            ['name' => 'auto_sync_merchant_from_pos', 'display_name' => 'Auto Sync Merchant From POS', 'category' => 'merchant'],
            ['name' => 'view_merchant_data', 'display_name' => 'View Merchant Data', 'category' => 'merchant'],

            ['name' => 'manage_own_users', 'display_name' => 'Manage Own Users', 'category' => 'user'],
            ['name' => 'view_all_users', 'display_name' => 'View All Users', 'category' => 'user'],
            ['name' => 'create_headquarter_user', 'display_name' => 'Create Headquarter User', 'category' => 'user'],
            ['name' => 'create_merchant_user', 'display_name' => 'Create Merchant User', 'category' => 'user'],
            ['name' => 'update_user_status', 'display_name' => 'Update User Status', 'category' => 'user'],
            ['name' => 'reset_user_password', 'display_name' => 'Reset User Password', 'category' => 'user'],
            ['name' => 'manage_own_merchant_users', 'display_name' => 'Manage Own Merchant Users', 'category' => 'user'],

            ['name' => 'manage_own_api_keys', 'display_name' => 'Manage Own API Keys', 'category' => 'api_key'],

            ['name' => 'view_dashboard_summary', 'display_name' => 'View Dashboard Summary', 'category' => 'dashboard'],
            ['name' => 'view_top_merchants', 'display_name' => 'View Top Merchants', 'category' => 'dashboard'],
            ['name' => 'view_payment_method_stats', 'display_name' => 'View Payment Method Stats', 'category' => 'dashboard'],
            ['name' => 'view_our_margin', 'display_name' => 'View Our Margin', 'category' => 'dashboard'],
            ['name' => 'view_paid_pending_failed_stats', 'display_name' => 'View Paid Pending Failed Stats', 'category' => 'dashboard'],

            ['name' => 'generate_transaction_report', 'display_name' => 'Generate Transaction Report', 'category' => 'report'],
            ['name' => 'generate_settlement_report', 'display_name' => 'Generate Settlement Report', 'category' => 'report'],
            ['name' => 'generate_difference_report', 'display_name' => 'Generate Difference Report', 'category' => 'report'],
            ['name' => 'generate_audit_report', 'display_name' => 'Generate Audit Report', 'category' => 'report'],
            ['name' => 'export_transaction_report', 'display_name' => 'Export Transaction Report', 'category' => 'report'],
            ['name' => 'export_settlement_report', 'display_name' => 'Export Settlement Report', 'category' => 'report'],
            ['name' => 'view_own_reports', 'display_name' => 'View Own Reports', 'category' => 'report'],
            ['name' => 'view_merchant_reports', 'display_name' => 'View Merchant Reports', 'category' => 'report'],

            ['name' => 'process_payments', 'display_name' => 'Process Payments', 'category' => 'payment'],
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
        // System Owner (Super Admin) - Full access
        $superAdmin = Role::where('name', 'system_owner')->first();
        $superAdmin?->syncPermissions(Permission::all());

        // System Owner Admin - All except finance operations
        $systemOwnerAdmin = Role::where('name', 'system_owner_admin')->first();
        $systemOwnerAdmin?->syncPermissions(
            Permission::whereNotIn('category', ['finance'])->get()
        );

        // System Owner Finance - Finance & settlement only
        $systemOwnerFinance = Role::where('name', 'system_owner_finance')->first();
        $systemOwnerFinance?->syncPermissions(
            Permission::whereIn('category', ['finance', 'settlement'])->get()
        );

        // System Owner Support - Read only access
        $systemOwnerSupport = Role::where('name', 'system_owner_support')->first();
        $systemOwnerSupport?->syncPermissions([
            'view_all_transactions_national',
            'view_regional_transactions',
            'view_merchant_list',
            'view_merchant_data',
            'view_audit_trails',
            'filter_by_date',
            'filter_by_payment_method',
            'filter_by_payment_gateway',
            'filter_by_merchant',
            'filter_by_regional',
            'filter_by_status',
        ]);

        // Client - Manage own entities & view own data
        $client = Role::where('name', 'client')->first();
        $client?->syncPermissions([
            // Manage own entities
            'manage_own_head_offices',
            'manage_own_merchants',
            'manage_own_users',
            'manage_own_api_keys',
            // Transaction viewing
            'view_own_merchant_transactions',
            'filter_by_date',
            'filter_by_payment_method',
            'filter_by_status',
            // Settlement
            'view_own_settlements',
            'view_settlement_history',
            'request_settlement',
            // Balance
            'view_available_balance',
            'view_pending_balance',
            'view_minus_balance',
            'view_balance_history',
            'view_client_debt',
            // Dashboard
            'view_dashboard_summary',
            'view_top_merchants',
            'view_payment_method_stats',
            'view_our_margin',
            'view_paid_pending_failed_stats',
            // MDR (view only)
            'view_mdr_configuration',
            // Reporting
            'generate_transaction_report',
            'generate_settlement_report',
            'generate_difference_report',
            'export_transaction_report',
            'export_settlement_report',
            'view_own_reports',
            'view_merchant_reports',
            // User management
            'create_merchant_user',
            'create_headquarter_user',
            'update_user_status',
            'reset_user_password',
            'manage_own_merchant_users',
        ]);

        // Client Admin - Same as client
        $clientAdmin = Role::where('name', 'client_admin')->first();
        $clientAdmin?->syncPermissions($client?->permissions ?? collect());

        // Client Finance - Finance read only
        $clientFinance = Role::where('name', 'client_finance')->first();
        $clientFinance?->syncPermissions([
            'view_own_merchant_transactions',
            'view_own_settlements',
            'view_settlement_history',
            'view_available_balance',
            'view_pending_balance',
            'view_minus_balance',
            'view_balance_history',
            'view_client_debt',
            'filter_by_date',
            'filter_by_status',
            'view_dashboard_summary',
            'view_payment_method_stats',
            'view_paid_pending_failed_stats',
            'generate_transaction_report',
            'generate_settlement_report',
            'generate_difference_report',
            'export_transaction_report',
            'export_settlement_report',
            'view_own_reports',
        ]);

        // Client Operations - Operations focused
        $clientOperations = Role::where('name', 'client_operations')->first();
        $clientOperations?->syncPermissions([
            'manage_own_merchants',
            'view_merchant_list',
            'view_merchant_data',
            'view_own_merchant_transactions',
            'filter_by_date',
            'filter_by_payment_method',
            'filter_by_status',
            'view_dashboard_summary',
            'view_payment_method_stats',
            'generate_transaction_report',
            'export_transaction_report',
            'view_own_reports',
            'create_merchant_user',
            'update_user_status',
        ]);

        // Head Office - View regional merchant data
        $headOffice = Role::where('name', 'head_office')->first();
        $headOffice?->syncPermissions([
            // Transaction viewing
            'view_merchant_transactions',
            'view_regional_transactions',
            'filter_by_date',
            'filter_by_payment_method',
            'filter_by_merchant',
            'filter_by_status',
            // Dashboard
            'view_dashboard_summary',
            'view_top_merchants',
            'view_payment_method_stats',
            'view_our_margin',
            'view_paid_pending_failed_stats',
            // Merchant data
            'view_merchant_list',
            'view_merchant_data',
            // Reporting
            'generate_transaction_report',
            'generate_settlement_report',
            'generate_difference_report',
            'export_transaction_report',
            'export_settlement_report',
            'view_merchant_reports',
            // User management
            'create_merchant_user',
            'update_user_status',
            'reset_user_password',
            'manage_own_merchant_users',
        ]);

        // Head Office Admin & Supervisor - Same as head office
        // Note: head_office_admin and head_office_supervisor roles have been removed

        // Merchant - View own transactions & request settlement
        $merchant = Role::where('name', 'merchant')->first();
        $merchant?->syncPermissions([
            'view_own_merchant_transactions',
            'filter_by_date',
            'filter_by_status',
            'view_dashboard_summary',
            'view_payment_method_stats',
            'request_settlement',
            'view_settlement_history',
            'process_payments',
            'view_merchant_data',
        ]);

        // Note: merchant_admin and merchant_cashier roles have been removed
    }
}
