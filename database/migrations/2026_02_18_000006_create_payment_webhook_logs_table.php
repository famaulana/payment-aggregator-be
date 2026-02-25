<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->string('direction', 20)->comment('inbound (from PG) or outbound (to client)');
            $table->string('event_type', 100)->nullable()->comment('payment.paid, payment.failed, etc.');
            $table->foreignId('transaction_id')->nullable()->constrained()->onDelete('set null');
            $table->string('gateway_code', 50)->nullable()->comment('PG code for inbound webhooks');
            $table->string('target_url', 1000)->nullable()->comment('Client callback URL for outbound webhooks');
            $table->json('payload')->nullable()->comment('Webhook payload content');
            $table->unsignedSmallInteger('response_status')->nullable()->comment('HTTP status of response');
            $table->json('response_body')->nullable();
            $table->unsignedTinyInteger('attempt_count')->default(1);
            $table->boolean('is_verified')->default(false)->comment('Whether signature was verified');
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['direction', 'event_type'], 'idx_webhook_direction_event');
            $table->index('transaction_id', 'idx_webhook_transaction');
            $table->index('gateway_code', 'idx_webhook_gateway');
            $table->index('created_at', 'idx_webhook_created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_logs');
    }
};
