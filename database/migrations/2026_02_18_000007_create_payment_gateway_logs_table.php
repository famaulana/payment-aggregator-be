<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('payment_gateway_id')->constrained()->onDelete('cascade');
            $table->string('action', 100)->comment('create_payment, get_status, refund, cancel');
            $table->string('request_url', 1000);
            $table->string('request_method', 10)->comment('GET|POST|PUT|DELETE');
            $table->json('request_headers')->nullable()->comment('Headers sent (secrets redacted)');
            $table->json('request_body')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable()->comment('HTTP status from PG');
            $table->json('response_body')->nullable();
            $table->unsignedInteger('processing_time_ms')->nullable();
            $table->boolean('is_success')->default(false);
            $table->timestamps();

            $table->index('transaction_id', 'idx_pg_log_transaction');
            $table->index('payment_gateway_id', 'idx_pg_log_gateway');
            $table->index('action', 'idx_pg_log_action');
            $table->index('is_success', 'idx_pg_log_success');
            $table->index('created_at', 'idx_pg_log_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_logs');
    }
};
