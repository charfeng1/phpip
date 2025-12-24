<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Audit log table for tracking all data changes for compliance and dispute resolution.
     * Captures who/what/when for every create, update, and delete operation on auditable models.
     */
    public function up(): void
    {
        // Skip if table already exists (e.g., loaded from postgres-schema.sql)
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Polymorphic relation to the audited model
            $table->string('auditable_type', 100)->comment('Model class name (e.g., App\\Models\\Matter)');
            $table->unsignedBigInteger('auditable_id')->comment('Primary key of the audited record');

            // Action type: created, updated, deleted
            $table->string('action', 20)->comment('Type of action: created, updated, deleted');

            // User who performed the action
            $table->string('user_login', 16)->nullable()->comment('Login of user who made the change');
            $table->string('user_name', 100)->nullable()->comment('Full name of user at time of action');

            // Before and after values (JSON format)
            $table->json('old_values')->nullable()->comment('Previous values before change (for update/delete)');
            $table->json('new_values')->nullable()->comment('New values after change (for create/update)');

            // Additional context
            $table->string('ip_address', 45)->nullable()->comment('IP address of the client');
            $table->string('user_agent', 500)->nullable()->comment('Browser/client user agent');
            $table->string('url', 500)->nullable()->comment('URL where the action was triggered');

            // Timestamps
            $table->timestamp('created_at')->useCurrent()->comment('When the action occurred');

            // Indexes for common queries
            $table->index(['auditable_type', 'auditable_id'], 'audit_logs_auditable_index');
            $table->index('user_login', 'audit_logs_user_index');
            $table->index('action', 'audit_logs_action_index');
            $table->index('created_at', 'audit_logs_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
