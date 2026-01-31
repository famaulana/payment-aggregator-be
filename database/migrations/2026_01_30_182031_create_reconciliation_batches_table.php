<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to store reconciliation batch information
     */
    public function up(): void
    {
        Schema::create('reconciliation_batches', function (Blueprint $table) {
            $table->id();
            $table->string('batch_code', 100)->unique();
            $table->date('period_start_date');
            $table->date('period_end_date');
            $table->date('reconciliation_date');
            $table->string('internal_file_path', 500)->nullable()->comment('Internal settlement report');
            $table->string('pg_file_path', 500)->nullable()->comment('Payment Gateway report');
            $table->integer('total_matched')->default(0);
            $table->integer('total_unmatched')->default(0);
            $table->integer('total_disputed')->default(0);
            $table->decimal('total_amount_discrepancy', 15, 2)->default(0);
            $table->string('status', 20)->default('pending')->comment('pending, processing, completed, cancelled');
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade')->comment('System Owner user ID');
            $table->timestamps();
            $table->timestamp('completed_at')->nullable();

            $table->index('batch_code', 'idx_batch_code');
            $table->index('status', 'idx_reconciliation_batches_status');
            $table->index(['period_start_date', 'period_end_date'], 'idx_period');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliation_batches');
    }
};
