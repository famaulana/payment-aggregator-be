<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->string('icon_url', 500)->nullable()->after('status');
            $table->unsignedBigInteger('min_amount')->nullable()->after('icon_url')->comment('Minimum transaction amount in IDR');
            $table->unsignedBigInteger('max_amount')->nullable()->after('min_amount')->comment('Maximum transaction amount in IDR');
            $table->boolean('is_redirect_based')->default(false)->after('max_amount')->comment('Whether PG requires redirect (e-wallet web, credit card)');
            $table->text('description')->nullable()->after('is_redirect_based');
            $table->unsignedSmallInteger('sort_order')->default(100)->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn(['icon_url', 'min_amount', 'max_amount', 'is_redirect_based', 'description', 'sort_order']);
        });
    }
};
