<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->string('sandbox_url', 255)->nullable()->after('api_url');
            $table->text('webhook_secret_encrypted')->nullable()->after('api_secret_encrypted')->comment('Encrypted secret for verifying inbound webhooks');
            $table->json('supported_methods')->nullable()->after('webhook_secret_encrypted')->comment('List of payment method codes supported');
            $table->integer('priority')->default(100)->after('settlement_sla')->comment('Routing priority, lower = higher priority');
            $table->foreignId('created_by')->nullable()->after('priority')->constrained('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('payment_gateways', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['sandbox_url', 'webhook_secret_encrypted', 'supported_methods', 'priority', 'created_by']);
        });
    }
};
