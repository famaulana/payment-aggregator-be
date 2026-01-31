<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to map transactions to settlement batches
     */
    public function up(): void
    {
        Schema::create('settlement_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('settlement_batch_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('mdr_amount', 15, 2);
            $table->decimal('net_amount', 15, 2);
            $table->timestamps();

            $table->unique(['settlement_batch_id', 'transaction_id'], 'unique_settlement_transaction');
            $table->index('settlement_batch_id', 'idx_settlement_batch_id');
            $table->index('transaction_id', 'idx_transaction_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlement_transactions');
    }
};
