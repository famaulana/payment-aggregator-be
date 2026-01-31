<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to store MDR (Merchant Discount Rate) configurations for our margin
     */
    public function up(): void
    {
        Schema::create('mdr_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_method_id')->constrained()->onDelete('cascade');
            $table->foreignId('payment_gateway_id')->constrained()->onDelete('cascade');
            $table->string('our_margin_type', 20)->comment('percentage, fixed, or mixed');
            $table->decimal('our_margin_percentage', 5, 2)->default(0);
            $table->decimal('our_margin_fixed', 15, 2)->default(0);
            $table->decimal('mdr_total_percentage', 5, 2);
            $table->timestamp('effective_from')->useCurrent();
            $table->timestamp('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');

            $table->index('payment_method_id', 'idx_payment_method_id');
            $table->index('is_active', 'idx_is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mdr_configurations');
    }
};
