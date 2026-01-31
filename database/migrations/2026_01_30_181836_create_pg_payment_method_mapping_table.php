<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to map payment gateways with their available payment methods and vendor margins
     */
    public function up(): void
    {
        Schema::create('pg_payment_method_mapping', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_gateway_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            $table->string('pg_method_code', 100)->nullable()->comment('Payment method code specific to PG');
            $table->string('vendor_margin_type', 20)->comment('percentage, fixed, or mixed');
            $table->decimal('vendor_margin_percentage', 5, 2)->default(0)->comment('Percentage margin from vendor (0.00 - 100.00)');
            $table->decimal('vendor_margin_fixed', 15, 2)->default(0)->comment('Fixed margin from vendor');
            $table->string('status', 20)->default('active')->comment('active or inactive');
            $table->timestamps();

            $table->unique(['payment_gateway_id', 'payment_method_id'], 'unique_pg_method');
            $table->index('payment_gateway_id', 'idx_payment_gateway_id');
            $table->index('payment_method_id', 'idx_payment_method_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pg_payment_method_mapping');
    }
};
