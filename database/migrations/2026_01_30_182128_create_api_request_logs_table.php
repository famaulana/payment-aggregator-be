<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to log all API requests for audit and debugging
     */
    public function up(): void
    {
        Schema::create('api_request_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('api_key_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('endpoint', 255);
            $table->string('method', 10);
            $table->json('request_headers')->nullable();
            $table->json('request_body')->nullable()->comment('Masked sensitive data');
            $table->integer('response_status');
            $table->json('response_body')->nullable()->comment('Masked sensitive data');
            $table->integer('processing_time_ms')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index('api_key_id', 'idx_api_key_id');
            $table->index('endpoint', 'idx_endpoint');
            $table->index('response_status', 'idx_response_status');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_request_logs');
    }
};
