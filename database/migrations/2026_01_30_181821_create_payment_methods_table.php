<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to store payment method configurations
     */
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('method_code', 50)->unique()->comment('qris, va_bca, va_bni, gopay, ovo, etc');
            $table->string('method_name', 255);
            $table->string('method_type', 20)->comment('qris, virtual_account, e_wallet, credit_card, paylater, transfer_bank');
            $table->string('status', 20)->default('active')->comment('active or inactive');
            $table->timestamps();

            $table->index('method_code', 'idx_method_code');
            $table->index('method_type', 'idx_method_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
