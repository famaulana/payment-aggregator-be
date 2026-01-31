<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to store settlement batch information for client payouts
     */
    public function up(): void
    {
        Schema::create('settlement_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('batch_code', 100)->unique()->comment('Internal batch code');
            $table->date('period_start_date')->comment('Start date of settlement period');
            $table->date('period_end_date')->comment('End date of settlement period');
            $table->date('settlement_date')->comment('Date when settlement was calculated');
            $table->integer('total_transactions');
            $table->decimal('total_gross_amount', 15, 2);
            $table->decimal('total_mdr_amount', 15, 2);
            $table->decimal('total_net_amount', 15, 2);
            $table->decimal('floating_fund_used', 15, 2)->default(0);
            $table->decimal('floating_fund_repaid', 15, 2)->default(0);
            $table->decimal('floating_fund_balance', 15, 2);
            $table->decimal('previous_mismatch_adjustment', 15, 2)->default(0)->comment('Adjustment from previous mismatch');
            $table->decimal('final_settlement_amount', 15, 2);
            $table->string('status', 20)->default('pending')->comment('pending, approved, rejected, processing, completed, cancelled');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null')->comment('System Owner user ID');
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('rejected_at')->nullable();
            $table->string('payout_method', 50)->nullable()->comment('bank_transfer, etc');
            $table->string('payout_reference', 100)->nullable()->comment('Bank transfer reference');
            $table->foreignId('payout_processed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('payout_processed_at')->nullable();
            $table->text('payout_notes')->nullable();
            $table->timestamps();

            $table->index('batch_code', 'idx_batch_code');
            $table->index('status', 'idx_settlement_batches_status');
            $table->index('settlement_date', 'idx_settlement_date');
            $table->index(['period_start_date', 'period_end_date'], 'idx_period');
            $table->index(['client_id', 'status'], 'idx_settlement_batches_client_status');
            $table->index(['period_start_date', 'period_end_date', 'settlement_date'], 'idx_settlement_batches_dates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_batches');
    }
};
