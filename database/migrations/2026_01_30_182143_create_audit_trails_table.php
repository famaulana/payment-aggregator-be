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
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null')->comment('User who performed the action');
            $table->string('user_role', 50)->nullable()->comment('Role of the user at time of action');
            $table->string('action_type', 100)->comment('create, update, delete, approve, reject, override, adjust');
            
            $table->string('auditable_type')->nullable()->comment('Entity type (polymorphic)');
            $table->unsignedBigInteger('auditable_id')->nullable()->comment('Entity ID (polymorphic)');
            
            $table->json('old_values')->nullable()->comment('Old values before change');
            $table->json('new_values')->nullable()->comment('New values after change');
            $table->text('changes_summary')->nullable()->comment('Human-readable summary of changes');
            
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('endpoint', 255)->nullable()->comment('API endpoint or route');
            $table->string('http_method', 10)->nullable()->comment('GET, POST, PUT, DELETE, etc');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id', 'idx_audit_user_id');
            $table->index('user_role', 'idx_user_role');
            $table->index('action_type', 'idx_action_type');
            $table->index(['auditable_type', 'auditable_id'], 'idx_auditable');
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
