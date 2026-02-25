<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pg_payment_method_mapping', function (Blueprint $table) {
            $table->unsignedBigInteger('min_amount')->nullable()->after('status')->comment('Min amount for this gateway+method');
            $table->unsignedBigInteger('max_amount')->nullable()->after('min_amount')->comment('Max amount for this gateway+method');
            $table->string('fee_type', 20)->default('percentage')->after('max_amount')->comment('Fee type charged to client: fixed|percentage|mixed');
            $table->decimal('fee_fixed', 15, 2)->default(0)->after('fee_type')->comment('Fixed fee charged to client (IDR)');
            $table->decimal('fee_percentage', 5, 2)->default(0)->after('fee_fixed')->comment('Percentage fee charged to client (0.00-100.00)');
            $table->json('channel_config')->nullable()->after('fee_percentage')->comment('Extra channel-specific config');
            $table->boolean('is_primary')->default(false)->after('channel_config')->comment('Primary gateway for this method');
        });
    }

    public function down(): void
    {
        Schema::table('pg_payment_method_mapping', function (Blueprint $table) {
            $table->dropColumn(['min_amount', 'max_amount', 'fee_type', 'fee_fixed', 'fee_percentage', 'channel_config', 'is_primary']);
        });
    }
};
