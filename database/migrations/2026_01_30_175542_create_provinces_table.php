<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table: provinces
     * Stores Indonesian province data with BPS codes
     * Reference: BPS (Badan Pusat Statistik) province codes
     */
    public function up(): void
    {
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('Province code from BPS (11, 12, 13, etc)');
            $table->string('name', 255);
            $table->timestamps();

            $table->index('code', 'idx_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provinces');
    }
};
