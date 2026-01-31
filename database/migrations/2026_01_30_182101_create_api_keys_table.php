<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to store API keys for client authentication
     */
    public function up(): void
    {
        Schema::create('api_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->string('key_name', 255);
            $table->string('api_key', 100)->comment('Plain API Key for display once');
            $table->string('api_key_hashed', 255)->unique()->comment('Hashed API Key for verification');
            $table->string('api_secret_hashed', 255)->comment('Hashed API Secret');
            $table->string('environment', 20)->default('dev')->comment('dev, staging, or production');
            $table->string('status', 20)->default('active')->comment('active, inactive, or revoked');
            $table->json('ip_whitelist')->nullable()->comment('Array of allowed IP addresses');
            $table->integer('rate_limit_per_minute')->default(60);
            $table->integer('rate_limit_per_hour')->default(1000);
            $table->timestamp('last_used_at')->nullable();
            $table->bigInteger('total_requests')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null')->comment('System Owner user ID');
            $table->foreignId('revoked_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('revoked_at')->nullable();

            $table->unique('api_key', 'api_key');
            $table->index('api_key_hashed', 'idx_api_key_hashed');
            $table->index('status', 'idx_api_keys_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_keys');
    }
};
