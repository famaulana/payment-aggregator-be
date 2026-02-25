<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->string('refund_id', 100)->unique()->comment('Internal refund ID: RFN-xxx');
            $table->string('pg_refund_id', 255)->nullable()->comment('Refund ID from Payment Gateway');
            $table->decimal('amount', 15, 2)->comment('Refund amount (partial or full)');
            $table->text('reason')->nullable();
            $table->string('status', 20)->default('pending')->comment('pending|success|failed');
            $table->string('ref_id', 255)->nullable()->comment('Idempotency key from client');
            $table->json('pg_response')->nullable()->comment('Raw response from PG');
            $table->timestamp('processed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('transaction_id', 'idx_refund_transaction_id');
            $table->index('status', 'idx_refund_status');
            $table->index('ref_id', 'idx_refund_ref_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refunds');
    }
};
