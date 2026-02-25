<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix entity_type values in users table to use morphMap aliases
        // instead of full class names stored by the old seeder
        $map = [
            'App\\Models\\Client'      => 'client',
            'App\\Models\\SystemOwner' => 'system_owner',
            'App\\Models\\HeadQuarter' => 'head_quarter',
            'App\\Models\\Merchant'    => 'merchant',
        ];

        foreach ($map as $fullClass => $alias) {
            DB::table('users')
                ->where('entity_type', $fullClass)
                ->update(['entity_type' => $alias]);
        }

        // Remove payment methods with electronic_money type (no longer supported)
        // Cascade delete will handle pg_payment_method_mapping and mdr_configurations
        DB::table('payment_methods')
            ->where('method_type', 'electronic_money')
            ->delete();
    }

    public function down(): void
    {
        // Revert aliases back to full class names
        $map = [
            'client'       => 'App\\Models\\Client',
            'system_owner' => 'App\\Models\\SystemOwner',
            'head_quarter' => 'App\\Models\\HeadQuarter',
            'merchant'     => 'App\\Models\\Merchant',
        ];

        foreach ($map as $alias => $fullClass) {
            DB::table('users')
                ->where('entity_type', $alias)
                ->update(['entity_type' => $fullClass]);
        }
    }
};
