<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to store client/company information
     */
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('client_code', 50)->unique()->comment('Internal client code');
            $table->string('client_name', 255);
            $table->string('business_type', 100)->nullable()->comment('Business type/industry');
            $table->string('kyb_status', 20)->default('not_required')->comment('not_required, pending, submitted, approved, rejected');
            $table->timestamp('kyb_submitted_at')->nullable();
            $table->timestamp('kyb_approved_at')->nullable();
            $table->timestamp('kyb_rejected_at')->nullable();
            $table->text('kyb_rejection_reason')->nullable();
            $table->time('settlement_time')->default('00:00:00')->comment('Default settlement time H+0');
            $table->json('settlement_config')->nullable()->comment('Custom settlement configuration per client');
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account_number', 50)->nullable();
            $table->string('bank_account_holder_name', 255)->nullable();
            $table->string('bank_branch', 255)->nullable();
            $table->string('pic_name', 255)->nullable();
            $table->string('pic_position', 100)->nullable();
            $table->string('pic_phone', 20)->nullable();
            $table->string('pic_email', 255)->nullable();
            $table->string('company_phone', 20)->nullable();
            $table->string('company_email', 255)->nullable();
            $table->decimal('available_balance', 15, 2)->default(0);
            $table->decimal('pending_balance', 15, 2)->default(0);
            $table->decimal('minus_balance', 15, 2)->default(0)->comment('Negative balance due to mismatch/overpayment');
            $table->foreignId('province_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('city_id')->nullable()->constrained()->onDelete('set null');
            $table->text('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->string('status', 20)->default('active')->comment('active, inactive, suspended');
            $table->timestamps();
            $table->unsignedBigInteger('created_by')->nullable()->comment('Created by System Owner');

            $table->index('client_code', 'idx_client_code');
            $table->index(['status', 'available_balance'], 'idx_clients_status_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
