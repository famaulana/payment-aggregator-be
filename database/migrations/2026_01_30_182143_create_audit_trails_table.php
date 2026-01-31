<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Table to store audit trail for all system activities
     */
    public function up(): void
    {
        Schema::create('audit_trails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null')->comment('NULL for system actions');
            $table->string('user_role', 50)->nullable();
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('set null');
            $table->string('action_type', 100)->comment('create, update, delete, approve, reject, override, adjust');
            $table->string('entity_type', 100)->comment('transaction, settlement, reconciliation, client, etc');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('changes_summary')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('endpoint', 255)->nullable();
            $table->string('http_method', 10)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_user_id');
            $table->index('action_type', 'idx_action_type');
            $table->index(['entity_type', 'entity_id'], 'idx_entity');
            $table->index('created_at', 'idx_created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trails');
    }
};
