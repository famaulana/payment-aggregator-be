<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to store merchant/store locations information
     */
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->foreignId('head_office_id')->nullable()->constrained()->onDelete('set null')->comment('Can be NULL if merchant directly under client');
            $table->string('merchant_code', 50)->comment('Internal merchant code');
            $table->string('merchant_name', 255);
            $table->foreignId('province_id')->constrained()->onDelete('cascade');
            $table->foreignId('city_id')->constrained()->onDelete('cascade');
            $table->foreignId('district_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('sub_district_id')->nullable()->constrained()->onDelete('set null');
            $table->text('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('pos_merchant_id', 100)->nullable()->comment('Merchant ID from POS system if synced');
            $table->string('status', 20)->default('active')->comment('active or inactive');
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->unique(['client_id', 'merchant_code'], 'unique_client_merchant_code');
            $table->index('merchant_code', 'idx_merchant_code');
            $table->index('status', 'idx_merchants_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
