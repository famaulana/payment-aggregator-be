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
        Schema::create('system_owners', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('code', 50)->unique()->comment('Unique system owner code');
            $table->string('business_type', 100)->nullable();
            $table->string('pic_name', 255)->nullable();
            $table->string('pic_position', 100)->nullable();
            $table->string('pic_phone', 20)->nullable();
            $table->string('pic_email', 255)->nullable();
            $table->string('company_phone', 20)->nullable();
            $table->string('company_email', 255)->nullable();
            $table->foreignId('province_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('city_id')->nullable()->constrained()->onDelete('set null');
            $table->text('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('status', 20)->default('active')->comment('active, inactive, suspended');
            $table->timestamps();

            $table->index('code', 'idx_system_owners_code');
            $table->index('status', 'idx_system_owners_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('system_owners');
    }
};
