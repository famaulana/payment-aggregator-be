<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to store Indonesian sub-districts (kelurahan/desa) data from BPS
     */
    public function up(): void
    {
        Schema::create('sub_districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained()->onDelete('cascade');
            $table->string('code', 15)->unique()->comment('Sub-district code from BPS');
            $table->string('name', 255);
            $table->string('postal_code', 10)->nullable();
            $table->timestamps();

            $table->index('district_id', 'idx_district_id');
            $table->index('code', 'idx_code');
            $table->index('postal_code', 'idx_postal_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_districts');
    }
};
