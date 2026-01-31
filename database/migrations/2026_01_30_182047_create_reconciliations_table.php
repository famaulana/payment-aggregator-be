<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to store reconciliation details between internal and PG records
     */
    public function up(): void
    {
        Schema::create('reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reconciliation_batch_id')->constrained()->onDelete('cascade');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null')->comment('NULL if transaction not found internally');
            $table->string('internal_transaction_id', 100)->nullable();
            $table->decimal('internal_amount', 15, 2)->nullable();
            $table->string('internal_status', 50)->nullable();
            $table->date('internal_settlement_date')->nullable();
            $table->string('pg_reference_id', 100)->nullable();
            $table->decimal('pg_amount', 15, 2)->nullable();
            $table->string('pg_status', 50)->nullable();
            $table->date('pg_settlement_date')->nullable();
            $table->string('match_type', 20)->comment('exact_match, partial_match, no_match, duplicate, missing');
            $table->string('match_status', 20)->comment('matched, unmatched, disputed');
            $table->decimal('amount_discrepancy', 15, 2)->default(0);
            $table->boolean('status_match')->default(false);
            $table->boolean('date_match')->default(false);
            $table->boolean('resolved')->default(false);
            $table->string('resolution_type', 20)->nullable()->comment('adjustment, write_off, follow_up');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->onDelete('set null')->comment('System Owner user ID');
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->decimal('client_debt_amount', 15, 2)->default(0);
            $table->boolean('client_debt_settled')->default(false);
            $table->timestamp('client_debt_settled_at')->nullable();
            $table->timestamps();

            $table->index('reconciliation_batch_id', 'idx_reconciliation_batch_id');
            $table->index('transaction_id', 'idx_transaction_id');
            $table->index('match_status', 'idx_match_status');
            $table->index('resolved', 'idx_resolved');
            $table->index(['reconciliation_batch_id', 'match_status'], 'idx_reconciliations_batch_status');
            $table->index(['client_debt_settled', 'client_debt_amount'], 'idx_reconciliations_client_debt');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reconciliations');
    }
};
