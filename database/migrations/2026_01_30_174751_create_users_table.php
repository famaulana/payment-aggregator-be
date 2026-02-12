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

            $table->string('entity_type')->nullable()->comment('SystemOwner, Client, HeadQuarter, or Merchant');
            $table->unsignedBigInteger('entity_id')->nullable()->comment('ID of the entity');

            $table->string('fcm_token', 500)->nullable();
            $table->string('status', 20)->default('active')->comment('active, inactive, suspended');
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable()->comment('User who created this record');

            $table->index('status', 'idx_users_status');
            $table->index('email', 'idx_email');
            $table->index('username', 'idx_username');
            $table->index(['entity_type', 'entity_id'], 'idx_users_entity');
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
