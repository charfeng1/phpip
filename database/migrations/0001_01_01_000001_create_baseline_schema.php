<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Baseline migration that creates the complete phpIP database schema.
 *
 * This migration loads the PostgreSQL schema dump to establish the foundation
 * for all subsequent migrations. It handles:
 * - 24 tables across 6 dependency tiers
 * - 5 views (including the critical 'users' view for Laravel auth)
 * - 13 stored functions (6 business logic + 7 trigger functions)
 * - 7 triggers
 *
 * IMPORTANT: This migration must run BEFORE all other migrations.
 * The timestamp 0001_01_01_000001 ensures it runs first.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Skip if tables already exist (for existing installations)
        if ($this->schemaAlreadyExists()) {
            return;
        }

        $schemaPath = database_path('schema/postgres-schema.sql');

        if (! file_exists($schemaPath)) {
            throw new RuntimeException(
                "Schema file not found: {$schemaPath}. " .
                "Please ensure the postgres-schema.sql file exists."
            );
        }

        $schema = file_get_contents($schemaPath);

        if ($schema === false) {
            throw new RuntimeException(
                "Failed to read schema file: {$schemaPath}. " .
                'Please check file permissions.'
            );
        }

        // PostgreSQL requires executing statements separately for some operations
        // We'll use unprepared() which allows multiple statements
        DB::unprepared($schema);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse dependency order
        $this->dropTriggers();
        $this->dropFunctions();
        $this->dropViews();
        $this->dropTables();
        $this->dropExtensions();
    }

    /**
     * Check if the schema already exists by checking for core tables.
     */
    private function schemaAlreadyExists(): bool
    {
        // Check for a few core tables that must exist
        return Schema::hasTable('country')
            && Schema::hasTable('actor')
            && Schema::hasTable('matter');
    }

    /**
     * Drop all triggers.
     */
    private function dropTriggers(): void
    {
        $triggers = [
            'classifier_before_insert' => 'classifier',
            'event_before_insert' => 'event',
            'event_before_update' => 'event',
            'matter_before_insert' => 'matter',
            'matter_before_update' => 'matter',
            'task_before_insert' => 'task',
            'task_before_update' => 'task',
        ];

        foreach ($triggers as $trigger => $table) {
            DB::statement("DROP TRIGGER IF EXISTS {$trigger} ON {$table}");
        }
    }

    /**
     * Drop all stored functions.
     */
    private function dropFunctions(): void
    {
        $functions = [
            // Business logic functions
            'tcase(TEXT)',
            'actor_list(INTEGER, TEXT)',
            'matter_status(INTEGER)',
            'compute_matter_uid(VARCHAR, VARCHAR, VARCHAR, VARCHAR, SMALLINT)',
            'insert_recurring_renewals(INTEGER, INTEGER, DATE, VARCHAR, VARCHAR)',
            'update_expired()',
            // Trigger functions
            'classifier_before_insert_func()',
            'event_before_insert_func()',
            'event_before_update_func()',
            'matter_before_insert_func()',
            'matter_before_update_func()',
            'task_before_insert_func()',
            'task_before_update_func()',
        ];

        foreach ($functions as $function) {
            DB::statement("DROP FUNCTION IF EXISTS {$function} CASCADE");
        }
    }

    /**
     * Drop all views.
     */
    private function dropViews(): void
    {
        $views = [
            'users',
            'event_lnk_list',
            'matter_actors',
            'matter_classifiers',
            'task_list',
        ];

        foreach ($views as $view) {
            DB::statement("DROP VIEW IF EXISTS {$view} CASCADE");
        }
    }

    /**
     * Drop all tables in reverse dependency order.
     */
    private function dropTables(): void
    {
        // Tier 6 - Audit
        Schema::dropIfExists('audit_logs');

        // Tier 5 - Laravel standard (these may be managed by other migrations)
        // Schema::dropIfExists('failed_jobs');
        // Schema::dropIfExists('password_resets');
        // Schema::dropIfExists('migrations');

        // Tier 4 - Relationships
        Schema::dropIfExists('renewals_logs');
        Schema::dropIfExists('event_class_lnk');
        Schema::dropIfExists('classifier');
        Schema::dropIfExists('matter_actor_lnk');

        // Tier 3 - Business core
        Schema::dropIfExists('task');
        Schema::dropIfExists('task_rules');
        Schema::dropIfExists('event');
        Schema::dropIfExists('matter');

        // Tier 2 - First-level FK
        Schema::dropIfExists('default_actor');
        Schema::dropIfExists('fees');
        Schema::dropIfExists('template_members');
        Schema::dropIfExists('classifier_value');
        Schema::dropIfExists('actor');

        // Tier 1 - Foundation
        Schema::dropIfExists('template_classes');
        Schema::dropIfExists('classifier_type');
        Schema::dropIfExists('event_name');
        Schema::dropIfExists('matter_type');
        Schema::dropIfExists('matter_category');
        Schema::dropIfExists('actor_role');
        Schema::dropIfExists('country');
    }

    /**
     * Drop extensions.
     */
    private function dropExtensions(): void
    {
        // Don't drop uuid-ossp as it may be used by other applications
        // DB::statement('DROP EXTENSION IF EXISTS "uuid-ossp"');
    }
};
