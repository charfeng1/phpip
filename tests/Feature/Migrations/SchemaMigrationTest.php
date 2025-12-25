<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Tests for the tier-based migrations that create the complete phpIP schema.
 *
 * These tests verify that migrate:fresh creates all required database objects:
 * - 24 tables across 6 dependency tiers
 * - 5 views (including critical 'users' view)
 * - 13 stored functions (6 business logic + 7 trigger functions)
 * - 7 triggers
 *
 * Uses PHPUnit data providers to consolidate repetitive tests into data-driven tests.
 */
class SchemaMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed the database after migrations run.
     * This ensures other tests that rely on DatabaseTransactions have seeded data.
     */
    protected $seed = true;

    // =========================================================================
    // DATA-DRIVEN TABLE STRUCTURE TESTS
    // =========================================================================

    /**
     * Test that tables exist and have the expected key columns.
     */
    #[DataProvider('tableStructureProvider')]
    public function test_tables_have_correct_structure(string $table, array $columns): void
    {
        $this->assertTrue(Schema::hasTable($table), "Table '{$table}' should exist");

        if (!empty($columns)) {
            $actualColumns = Schema::getColumnListing($table);
            foreach ($columns as $column) {
                $this->assertContains(
                    $column,
                    $actualColumns,
                    "Table '{$table}' should have column '{$column}'"
                );
            }
        }
    }

    /**
     * Provides table names and their expected columns for structure verification.
     * Organized by dependency tiers.
     *
     * Note: Tables with empty column arrays or partial column lists are managed
     * by third-party packages (Laravel framework or Auditable package) whose
     * structure can change between versions. These tables are still verified
     * to exist, but full column validation is not performed.
     */
    public static function tableStructureProvider(): array
    {
        return [
            // Tier 1: Foundation Tables (No FK Dependencies)
            'country' => ['country', ['iso', 'name', 'ep', 'wo']],
            'actor_role' => ['actor_role', ['code', 'name', 'shareable']],
            'matter_category' => ['matter_category', ['code', 'category']],
            'matter_type' => ['matter_type', ['code', 'type']],
            'event_name' => ['event_name', ['code', 'name', 'is_task']],
            'classifier_type' => ['classifier_type', ['code', 'type']],
            'template_classes' => ['template_classes', ['id', 'name']],

            // Tier 2: First-Level FK Dependencies
            'actor' => ['actor', ['id', 'name', 'login', 'password', 'default_role']],
            'classifier_value' => ['classifier_value', ['id', 'value', 'type_code']],
            'template_members' => ['template_members', ['id', 'class_id']],
            'fees' => ['fees', ['id', 'for_country', 'for_category']],
            'default_actor' => ['default_actor', ['id', 'actor_id', 'role']],

            // Tier 3: Business Core Tables
            'matter' => ['matter', ['id', 'caseref', 'uid', 'category_code', 'country']],
            'event' => ['event', ['id', 'matter_id', 'code', 'event_date']],
            'task_rules' => ['task_rules', ['id', 'task', 'trigger_event']],
            'task' => ['task', ['id', 'trigger_id', 'code', 'due_date']],

            // Tier 4: Relationship Tables
            'matter_actor_lnk' => ['matter_actor_lnk', ['id', 'matter_id', 'actor_id', 'role']],
            'classifier' => ['classifier', ['id', 'matter_id', 'type_code']],
            'event_class_lnk' => ['event_class_lnk', ['id', 'event_name_code', 'template_class_id']],
            'renewals_logs' => ['renewals_logs', ['id', 'task_id']],

            // Tier 5: Laravel Standard Tables (framework-managed, existence-only check)
            'migrations' => ['migrations', []],
            'password_resets' => ['password_resets', []],
            'failed_jobs' => ['failed_jobs', []],

            // Tier 6: Audit Table (managed by Auditable package, partial key columns)
            'audit_logs' => ['audit_logs', ['id', 'auditable_type', 'auditable_id']],
        ];
    }

    // =========================================================================
    // DATA-DRIVEN VIEW TESTS
    // =========================================================================

    /**
     * Test that views exist and optionally verify their key columns.
     */
    #[DataProvider('viewProvider')]
    public function test_views_exist(string $view, array $expectedColumns = []): void
    {
        $views = DB::select("SELECT viewname FROM pg_views WHERE schemaname = 'public' AND viewname = ?", [$view]);
        $this->assertCount(1, $views, "The '{$view}' view should exist");

        // Verify key columns if provided (e.g., for critical views like 'users')
        if (!empty($expectedColumns)) {
            $columns = DB::select("
                SELECT column_name FROM information_schema.columns
                WHERE table_schema = 'public' AND table_name = ?
                ORDER BY ordinal_position
            ", [$view]);

            $columnNames = array_map(fn ($c) => $c->column_name, $columns);

            foreach ($expectedColumns as $column) {
                $this->assertContains(
                    $column,
                    $columnNames,
                    "View '{$view}' should have column '{$column}'"
                );
            }
        }
    }

    /**
     * Provides view names and their expected columns for verification.
     * The 'users' view has explicit column checks as it's critical for authentication.
     */
    public static function viewProvider(): array
    {
        return [
            // Check key Laravel auth columns exist in users view
            'users_view' => ['users', ['id', 'name', 'email', 'password', 'login', 'remember_token']],
            'event_lnk_list_view' => ['event_lnk_list', []],
            'matter_actors_view' => ['matter_actors', []],
            'matter_classifiers_view' => ['matter_classifiers', []],
            'task_list_view' => ['task_list', []],
        ];
    }

    // =========================================================================
    // DATA-DRIVEN FUNCTION TESTS
    // =========================================================================

    /**
     * Test that stored functions exist.
     */
    #[DataProvider('functionProvider')]
    public function test_functions_exist(string $functionName): void
    {
        $result = DB::select("SELECT proname FROM pg_proc WHERE proname = ?", [$functionName]);
        $this->assertCount(1, $result, "The '{$functionName}' function should exist");
    }

    /**
     * Provides function names to verify existence.
     */
    public static function functionProvider(): array
    {
        return [
            'tcase' => ['tcase'],
            'actor_list' => ['actor_list'],
            'matter_status' => ['matter_status'],
            'compute_matter_uid' => ['compute_matter_uid'],
            'insert_recurring_renewals' => ['insert_recurring_renewals'],
            'update_expired' => ['update_expired'],
        ];
    }

    // =========================================================================
    // DATA-DRIVEN TRIGGER TESTS
    // =========================================================================

    /**
     * Test that triggers exist.
     */
    #[DataProvider('triggerProvider')]
    public function test_triggers_exist(string $triggerName): void
    {
        $result = DB::select("
            SELECT trigger_name FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = ?
        ", [$triggerName]);

        $this->assertCount(1, $result, "The '{$triggerName}' trigger should exist");
    }

    /**
     * Provides trigger names to verify existence.
     */
    public static function triggerProvider(): array
    {
        return [
            'classifier_before_insert' => ['classifier_before_insert'],
            'event_before_insert' => ['event_before_insert'],
            'event_before_update' => ['event_before_update'],
            'matter_before_insert' => ['matter_before_insert'],
            'matter_before_update' => ['matter_before_update'],
            'task_before_insert' => ['task_before_insert'],
            'task_before_update' => ['task_before_update'],
        ];
    }

    // =========================================================================
    // SUMMARY TESTS (Aggregate Verification)
    // =========================================================================

    public function test_all_expected_tables_exist(): void
    {
        $expectedTables = [
            // Tier 1
            'country', 'actor_role', 'matter_category', 'matter_type',
            'event_name', 'classifier_type', 'template_classes',
            // Tier 2
            'actor', 'classifier_value', 'template_members', 'fees', 'default_actor',
            // Tier 3
            'matter', 'event', 'task_rules', 'task',
            // Tier 4
            'matter_actor_lnk', 'classifier', 'event_class_lnk', 'renewals_logs',
            // Tier 5
            'migrations', 'password_resets', 'failed_jobs',
            // Tier 6
            'audit_logs',
        ];

        foreach ($expectedTables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table '{$table}' should exist");
        }

        $this->assertCount(24, $expectedTables);
    }

    public function test_all_expected_views_exist(): void
    {
        $expectedViews = ['users', 'event_lnk_list', 'matter_actors', 'matter_classifiers', 'task_list'];

        $views = DB::select("SELECT viewname FROM pg_views WHERE schemaname = 'public'");
        $viewNames = array_map(fn ($v) => $v->viewname, $views);

        foreach ($expectedViews as $view) {
            $this->assertContains($view, $viewNames, "View '{$view}' should exist");
        }
    }

    public function test_all_expected_functions_exist(): void
    {
        $expectedFunctions = [
            // Business logic functions (6)
            'tcase',
            'actor_list',
            'matter_status',
            'compute_matter_uid',
            'insert_recurring_renewals',
            'update_expired',
            // Trigger functions (7)
            'classifier_before_insert_func',
            'event_before_insert_func',
            'event_before_update_func',
            'matter_before_insert_func',
            'matter_before_update_func',
            'task_before_insert_func',
            'task_before_update_func',
        ];

        $functions = DB::select("SELECT proname FROM pg_proc JOIN pg_namespace ON pg_proc.pronamespace = pg_namespace.oid WHERE nspname = 'public'");
        $functionNames = array_map(fn ($f) => $f->proname, $functions);

        foreach ($expectedFunctions as $function) {
            $this->assertContains($function, $functionNames, "Function '{$function}' should exist");
        }

        $this->assertCount(13, $expectedFunctions);
    }

    public function test_all_expected_triggers_exist(): void
    {
        $expectedTriggers = [
            'classifier_before_insert',
            'event_before_insert',
            'event_before_update',
            'matter_before_insert',
            'matter_before_update',
            'task_before_insert',
            'task_before_update',
        ];

        $triggers = DB::select("SELECT trigger_name FROM information_schema.triggers WHERE trigger_schema = 'public'");
        $triggerNames = array_map(fn ($t) => $t->trigger_name, $triggers);

        foreach ($expectedTriggers as $trigger) {
            $this->assertContains($trigger, $triggerNames, "Trigger '{$trigger}' should exist");
        }
    }
}
