<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username', 100)->unique();
            $table->string('email', 255)->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password', 255);
            $table->rememberToken();
            $table->string('full_name', 255);
            
            $table->unsignedBigInteger('client_id')->nullable()->comment('For client, head_office, merchant roles');
            $table->unsignedBigInteger('head_office_id')->nullable()->comment('For head_office, merchant roles');
            $table->unsignedBigInteger('merchant_id')->nullable()->comment('For merchant role only');
            
            $table->string('fcm_token', 500)->nullable();
            $table->string('status', 20)->default('active')->comment('active, inactive, suspended');
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable()->comment('User who created this record');

            $table->index('status', 'idx_users_status');
            $table->index('email', 'idx_email');
            $table->index('username', 'idx_username');
            $table->index(['status', 'client_id'], 'idx_users_status_client');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
