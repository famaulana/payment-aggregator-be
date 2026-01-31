<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to store user notifications (FCM, in-app, etc.)
     */
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('notification_type', 100)->comment('settlement_approved, settlement_rejected, mismatch_found, balance_adjusted, payout_processed');
            $table->string('title', 255);
            $table->text('message');
            $table->json('data')->nullable()->comment('Additional data for notification');
            $table->string('reference_type', 50)->nullable()->comment('settlement_batch, reconciliation, transaction');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('status', 20)->default('pending')->comment('pending, sent, delivered, read, failed');
            $table->string('fcm_message_id', 200)->nullable();
            $table->json('fcm_response')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->timestamp('sent_at')->nullable();

            $table->index('user_id', 'idx_user_id');
            $table->index('notification_type', 'idx_notification_type');
            $table->index('status', 'idx_status');
            $table->index('is_read', 'idx_is_read');
            $table->index('created_at', 'idx_created_at');
            $table->index(['user_id', 'status', 'created_at'], 'idx_notifications_user_status');
            $table->index(['reference_type', 'reference_id'], 'idx_notifications_reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
