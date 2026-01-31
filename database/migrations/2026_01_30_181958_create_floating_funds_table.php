<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to track floating fund movements for settlements
     */
    public function up(): void
    {
        Schema::create('floating_funds', function (Blueprint $table) {
            $table->id();
            $table->string('movement_type', 20)->comment('allocation, usage, repayment, adjustment');
            $table->decimal('amount', 15, 2)->comment('Positive for allocation, negative for usage/repayment');
            $table->foreignId('settlement_batch_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->decimal('total_allocated', 15, 2)->comment('Total allocated to clients');
            $table->decimal('total_used', 15, 2)->comment('Total used for settlements');
            $table->decimal('available_balance', 15, 2);
            $table->text('description')->nullable();
            $table->string('reference_number', 100)->nullable()->comment('External reference number');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade')->comment('System Owner user ID');
            $table->timestamps();

            $table->index('movement_type', 'idx_movement_type');
            $table->index('settlement_batch_id', 'idx_settlement_batch_id');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('floating_funds');
    }
};
