<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create Tier 6 audit logging table.
 *
 * Table created:
 * - audit_logs: Tracks all data changes for compliance and dispute resolution
 *
 * This table captures who/what/when for every create, update, and delete
 * operation on auditable models.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('audit_logs')) {
            return;
        }

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type', 100);
            $table->unsignedBigInteger('auditable_id');
            $table->string('action', 20);
            $table->string('user_login', 16)->nullable();
            $table->string('user_name', 100)->nullable();
            $table->jsonb('old_values')->nullable();
            $table->jsonb('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->string('url', 500)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        // Add indexes
        DB::statement('CREATE INDEX audit_logs_auditable_index ON audit_logs (auditable_type, auditable_id)');
        DB::statement('CREATE INDEX audit_logs_user_index ON audit_logs (user_login)');
        DB::statement('CREATE INDEX audit_logs_action_index ON audit_logs (action)');
        DB::statement('CREATE INDEX audit_logs_created_index ON audit_logs (created_at)');

        // Add comments
        DB::statement("COMMENT ON COLUMN audit_logs.auditable_type IS 'Model class name (e.g., App\\Models\\Matter)'");
        DB::statement("COMMENT ON COLUMN audit_logs.auditable_id IS 'Primary key of the audited record'");
        DB::statement("COMMENT ON COLUMN audit_logs.action IS 'Type of action: created, updated, deleted'");
        DB::statement("COMMENT ON COLUMN audit_logs.user_login IS 'Login of user who made the change'");
        DB::statement("COMMENT ON COLUMN audit_logs.user_name IS 'Full name of user at time of action'");
        DB::statement("COMMENT ON COLUMN audit_logs.old_values IS 'Previous values before change (for update/delete)'");
        DB::statement("COMMENT ON COLUMN audit_logs.new_values IS 'New values after change (for create/update)'");
        DB::statement("COMMENT ON COLUMN audit_logs.ip_address IS 'IP address of the client'");
        DB::statement("COMMENT ON COLUMN audit_logs.user_agent IS 'Browser/client user agent'");
        DB::statement("COMMENT ON COLUMN audit_logs.url IS 'URL where the action was triggered'");
        DB::statement("COMMENT ON COLUMN audit_logs.created_at IS 'When the action occurred'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
