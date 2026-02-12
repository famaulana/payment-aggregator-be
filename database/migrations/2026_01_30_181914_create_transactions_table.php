<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to store transaction/payment records
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('merchant_id')->constrained()->onDelete('cascade');
            $table->foreignId('head_quarter_id')->nullable()->constrained('head_quarters')->onDelete('set null');
            $table->string('transaction_id', 100)->unique()->comment('Internal transaction ID');
            $table->string('pg_reference_id', 100)->nullable()->comment('Reference ID from Payment Gateway');
            $table->string('pos_reference_id', 100)->nullable()->comment('Reference ID from POS/Ticketing system');
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_gateway_id')->constrained()->onDelete('cascade');
            $table->decimal('gross_amount', 15, 2)->comment('Total payment from customer');
            $table->decimal('vendor_margin', 15, 2)->default(0)->comment('Fee from Payment Gateway');
            $table->decimal('our_margin', 15, 2)->default(0)->comment('Internal/application fee');
            $table->decimal('mdr_amount', 15, 2)->default(0)->comment('Total MDR (vendor_margin + our_margin)');
            $table->decimal('net_amount', 15, 2)->comment('Net amount received by merchant');
            $table->string('status', 20)->default('pending')->comment('pending, paid, failed, expired, refunded');
            $table->string('original_status', 20)->default('pending')->comment('Original status before any override');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->string('payment_type', 50)->nullable()->comment('qris, va, e-wallet, etc');
            $table->string('account_number', 100)->nullable()->comment('VA number or account number');
            $table->string('customer_name', 255)->nullable();
            $table->string('customer_email', 255)->nullable();
            $table->string('customer_phone', 20)->nullable();
            $table->string('settlement_status', 20)->default('pending')->comment('pending, in_settlement, settled');
            $table->date('settlement_date')->nullable();
            $table->foreignId('settlement_batch_id')->nullable()->constrained('settlement_batches')->onDelete('set null');
            $table->boolean('is_overridden')->default(false);
            $table->foreignId('overridden_by')->nullable()->constrained('users')->onDelete('set null')->comment('System Owner user ID');
            $table->timestamp('overridden_at')->nullable();
            $table->text('override_reason')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable()->comment('Additional data from PG or POS');
            $table->timestamps();

            $table->index('transaction_id', 'idx_transaction_id');
            $table->index('merchant_id', 'idx_merchant_id');
            $table->index('status', 'idx_transactions_status');
            $table->index('settlement_status', 'idx_settlement_status');
            $table->index('paid_at', 'idx_paid_at');
            $table->index('created_at', 'idx_created_at');
            $table->index(['client_id', 'status', 'created_at'], 'idx_client_status_date');
            $table->index(['client_id', 'created_at'], 'idx_transactions_client_date');
            $table->index(['settlement_status', 'created_at'], 'idx_transactions_settlement_status_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
