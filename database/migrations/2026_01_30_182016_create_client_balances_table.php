<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to track client balance movements and history
     */
    public function up(): void
    {
        Schema::create('client_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('transaction_type', 50)->comment('settlement, adjustment, correction, payment');
            $table->decimal('amount', 15, 2)->comment('Positive for addition, negative for deduction');
            $table->decimal('available_balance_before', 15, 2);
            $table->decimal('available_balance_after', 15, 2);
            $table->decimal('pending_balance_before', 15, 2);
            $table->decimal('pending_balance_after', 15, 2);
            $table->decimal('minus_balance_before', 15, 2);
            $table->decimal('minus_balance_after', 15, 2);
            $table->string('reference_type', 50)->nullable()->comment('settlement_batch, transaction, manual_adjustment');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->comment('System Owner user ID for adjustments');
            $table->timestamps();

            $table->index('client_id', 'idx_client_id');
            $table->index('transaction_type', 'idx_transaction_type');
            $table->index('created_at', 'idx_created_at');
            $table->index(['reference_type', 'reference_id'], 'idx_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_balances');
    }
};
