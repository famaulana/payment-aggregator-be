<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('merchant_ref', 255)->nullable()->after('transaction_id')->comment('Order/reference ID from client system');
            $table->string('currency', 3)->default('IDR')->after('merchant_ref');
            $table->string('pg_checkout_url', 1000)->nullable()->after('pg_reference_id')->comment('Checkout URL for redirect-based payments');
            $table->string('pg_deeplink_url', 1000)->nullable()->after('pg_checkout_url')->comment('Mobile deeplink URL for e-wallets');
            $table->text('pg_qr_string')->nullable()->after('pg_deeplink_url')->comment('Raw QR string for QRIS');
            $table->string('pg_va_number', 50)->nullable()->after('pg_qr_string')->comment('Virtual account number');
            $table->decimal('refunded_amount', 15, 2)->default(0)->after('net_amount')->comment('Total amount refunded (for partial refunds)');
            $table->string('callback_url', 1000)->nullable()->after('metadata')->comment('Webhook callback URL override');
            $table->string('redirect_url', 1000)->nullable()->after('callback_url')->comment('Redirect URL override for redirect-based PG');
            $table->json('items')->nullable()->after('redirect_url')->comment('Item details for the transaction');

            $table->index('merchant_ref', 'idx_merchant_ref');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_merchant_ref');
            $table->dropColumn(['merchant_ref', 'currency', 'pg_checkout_url', 'pg_deeplink_url', 'pg_qr_string', 'pg_va_number', 'refunded_amount', 'callback_url', 'redirect_url', 'items']);
        });
    }
};
