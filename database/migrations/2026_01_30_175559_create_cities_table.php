<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: cities
     * Stores city/regency data with BPS codes
     * Type can be 'kabupaten' or 'kota'
     */
    public function up(): void
    {
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained()->onDelete('cascade');
            $table->string('code', 10)->unique()->comment('City code from BPS (1101, 3171, etc)');
            $table->string('name', 255);
            $table->string('type', 20)->default('kabupaten')->comment('kabupaten or kota');
            $table->timestamps();

            $table->index(['province_id'], 'idx_province_id');
            $table->index(['code'], 'idx_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cities');
    }
};
