<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to store payment gateway configurations
     */
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('pg_code', 50)->unique()->comment('Internal PG code: bayarind, sti, cashup, cring, etc');
            $table->string('pg_name', 255);
            $table->string('api_url', 255);
            $table->text('api_key_encrypted')->comment('Encrypted API key');
            $table->text('api_secret_encrypted')->comment('Encrypted API secret');
            $table->string('status', 20)->default('active')->comment('active or inactive');
            $table->string('environment', 20)->default('dev')->comment('dev, staging, or production');
            $table->integer('settlement_sla')->default(1)->comment('Settlement SLA in days (T+1, T+2, etc)');
            $table->timestamps();

            $table->index('pg_code', 'idx_pg_code');
            $table->index('status', 'idx_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
