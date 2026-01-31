<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to store client KYB (Know Your Business) verification data
     */
    public function up(): void
    {
        Schema::create('client_kyb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->unique()->constrained()->onDelete('cascade');
            $table->string('business_type', 20)->comment('pt, cv, firma, ud, koperasi, yayasan, perorangan, other');
            $table->string('business_name_legal', 255)->comment('Legal business name');
            $table->string('business_industry', 100)->nullable()->comment('Business industry/sector');
            $table->string('nib', 50)->nullable()->comment('Nomor Induk Berusaha');
            $table->string('npwp', 50)->nullable()->comment('Tax Identification Number');
            $table->string('siup', 50)->nullable()->comment('Business License Number');
            $table->string('tdp', 50)->nullable()->comment('Company Registration Certificate');
            $table->string('akta_pendirian_number', 100)->nullable()->comment('Deed of Establishment Number');
            $table->date('akta_pendirian_date')->nullable();
            $table->string('akta_pendirian_notaris', 255)->nullable()->comment('Notary name');
            $table->string('sk_kemenkumham_number', 100)->nullable()->comment('Ministry of Law and Human Rights Approval Number');
            $table->date('sk_kemenkumham_date')->nullable();
            $table->foreignId('business_province_id')->nullable()->constrained('provinces')->onDelete('set null');
            $table->foreignId('business_city_id')->nullable()->constrained('cities')->onDelete('set null');
            $table->foreignId('business_district_id')->nullable()->constrained('districts')->onDelete('set null');
            $table->foreignId('business_sub_district_id')->nullable()->constrained('sub_districts')->onDelete('set null');
            $table->text('business_address')->nullable();
            $table->string('business_postal_code', 10)->nullable();
            $table->string('pic_name', 255)->comment('Person in Charge name');
            $table->string('pic_position', 100)->nullable()->comment('Position/job title');
            $table->string('pic_phone', 20);
            $table->string('pic_email', 255);
            $table->string('pic_ktp', 20)->nullable()->comment('Personal ID Number');
            $table->string('director_name', 255)->nullable();
            $table->string('director_ktp', 20)->nullable();
            $table->string('director_phone', 20)->nullable();
            $table->string('director_email', 255)->nullable();
            $table->string('doc_nib_file', 500)->nullable();
            $table->string('doc_npwp_file', 500)->nullable();
            $table->string('doc_siup_file', 500)->nullable();
            $table->string('doc_akta_pendirian_file', 500)->nullable();
            $table->string('doc_sk_kemenkumham_file', 500)->nullable();
            $table->string('doc_ktp_pic_file', 500)->nullable();
            $table->string('doc_ktp_director_file', 500)->nullable();
            $table->string('doc_bank_statement_file', 500)->nullable()->comment('Bank statement/transaction record');
            $table->string('doc_selfie_ktp_file', 500)->nullable()->comment('Selfie with ID card');
            $table->string('doc_business_photo_file', 500)->nullable()->comment('Business location photo');
            $table->json('doc_additional_files')->nullable()->comment('Additional files in array format');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null')->comment('System Owner user ID');
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->json('metadata')->nullable()->comment('Additional flexible data');
            $table->timestamps();

            $table->index('client_id', 'idx_client_id');
            $table->index('business_type', 'idx_business_type');
            $table->index('npwp', 'idx_npwp');
            $table->index('nib', 'idx_nib');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_kyb');
    }
};
