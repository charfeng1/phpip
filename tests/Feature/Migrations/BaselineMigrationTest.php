<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests for the baseline migration that creates the complete phpIP schema.
 *
 * These tests verify that migrate:fresh creates all required database objects:
 * - 24 tables across 6 dependency tiers
 * - 5 views (including critical 'users' view)
 * - 13 stored functions (6 business logic + 7 trigger functions)
 * - 7 triggers
 */
class BaselineMigrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Seed the database after migrations run.
     * This ensures other tests that rely on DatabaseTransactions have seeded data.
     */
    protected $seed = true;

    // =========================================================================
    // TIER 1: Foundation Tables (No FK Dependencies)
    // =========================================================================

    public function test_tier1_country_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('country'));
        $this->assertTrue(Schema::hasColumn('country', 'iso'));
        $this->assertTrue(Schema::hasColumn('country', 'name'));
        $this->assertTrue(Schema::hasColumn('country', 'ep'));
        $this->assertTrue(Schema::hasColumn('country', 'wo'));
    }

    public function test_tier1_actor_role_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('actor_role'));
        $this->assertTrue(Schema::hasColumn('actor_role', 'code'));
        $this->assertTrue(Schema::hasColumn('actor_role', 'name'));
        $this->assertTrue(Schema::hasColumn('actor_role', 'shareable'));
    }

    public function test_tier1_matter_category_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('matter_category'));
        $this->assertTrue(Schema::hasColumn('matter_category', 'code'));
        $this->assertTrue(Schema::hasColumn('matter_category', 'category'));
    }

    public function test_tier1_matter_type_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('matter_type'));
        $this->assertTrue(Schema::hasColumn('matter_type', 'code'));
        $this->assertTrue(Schema::hasColumn('matter_type', 'type'));
    }

    public function test_tier1_event_name_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('event_name'));
        $this->assertTrue(Schema::hasColumn('event_name', 'code'));
        $this->assertTrue(Schema::hasColumn('event_name', 'name'));
        $this->assertTrue(Schema::hasColumn('event_name', 'is_task'));
    }

    public function test_tier1_classifier_type_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('classifier_type'));
        $this->assertTrue(Schema::hasColumn('classifier_type', 'code'));
        $this->assertTrue(Schema::hasColumn('classifier_type', 'type'));
    }

    public function test_tier1_template_classes_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('template_classes'));
        $this->assertTrue(Schema::hasColumn('template_classes', 'id'));
        $this->assertTrue(Schema::hasColumn('template_classes', 'name'));
    }

    // =========================================================================
    // TIER 2: First-Level FK Dependencies
    // =========================================================================

    public function test_tier2_actor_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('actor'));
        $this->assertTrue(Schema::hasColumn('actor', 'id'));
        $this->assertTrue(Schema::hasColumn('actor', 'name'));
        $this->assertTrue(Schema::hasColumn('actor', 'login'));
        $this->assertTrue(Schema::hasColumn('actor', 'password'));
        $this->assertTrue(Schema::hasColumn('actor', 'default_role'));
    }

    public function test_tier2_classifier_value_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('classifier_value'));
        $this->assertTrue(Schema::hasColumn('classifier_value', 'id'));
        $this->assertTrue(Schema::hasColumn('classifier_value', 'value'));
        $this->assertTrue(Schema::hasColumn('classifier_value', 'type_code'));
    }

    public function test_tier2_template_members_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('template_members'));
        $this->assertTrue(Schema::hasColumn('template_members', 'id'));
        $this->assertTrue(Schema::hasColumn('template_members', 'class_id'));
    }

    public function test_tier2_fees_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('fees'));
        $this->assertTrue(Schema::hasColumn('fees', 'id'));
        $this->assertTrue(Schema::hasColumn('fees', 'for_country'));
        $this->assertTrue(Schema::hasColumn('fees', 'for_category'));
    }

    public function test_tier2_default_actor_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('default_actor'));
        $this->assertTrue(Schema::hasColumn('default_actor', 'id'));
        $this->assertTrue(Schema::hasColumn('default_actor', 'actor_id'));
        $this->assertTrue(Schema::hasColumn('default_actor', 'role'));
    }

    // =========================================================================
    // TIER 3: Business Core Tables
    // =========================================================================

    public function test_tier3_matter_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('matter'));
        $this->assertTrue(Schema::hasColumn('matter', 'id'));
        $this->assertTrue(Schema::hasColumn('matter', 'caseref'));
        $this->assertTrue(Schema::hasColumn('matter', 'uid'));
        $this->assertTrue(Schema::hasColumn('matter', 'category_code'));
        $this->assertTrue(Schema::hasColumn('matter', 'country'));
    }

    public function test_tier3_event_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('event'));
        $this->assertTrue(Schema::hasColumn('event', 'id'));
        $this->assertTrue(Schema::hasColumn('event', 'matter_id'));
        $this->assertTrue(Schema::hasColumn('event', 'code'));
        $this->assertTrue(Schema::hasColumn('event', 'event_date'));
    }

    public function test_tier3_task_rules_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('task_rules'));
        $this->assertTrue(Schema::hasColumn('task_rules', 'id'));
        $this->assertTrue(Schema::hasColumn('task_rules', 'task'));
        $this->assertTrue(Schema::hasColumn('task_rules', 'trigger_event'));
    }

    public function test_tier3_task_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('task'));
        $this->assertTrue(Schema::hasColumn('task', 'id'));
        $this->assertTrue(Schema::hasColumn('task', 'trigger_id'));
        $this->assertTrue(Schema::hasColumn('task', 'code'));
        $this->assertTrue(Schema::hasColumn('task', 'due_date'));
    }

    // =========================================================================
    // TIER 4: Relationship Tables
    // =========================================================================

    public function test_tier4_matter_actor_lnk_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('matter_actor_lnk'));
        $this->assertTrue(Schema::hasColumn('matter_actor_lnk', 'id'));
        $this->assertTrue(Schema::hasColumn('matter_actor_lnk', 'matter_id'));
        $this->assertTrue(Schema::hasColumn('matter_actor_lnk', 'actor_id'));
        $this->assertTrue(Schema::hasColumn('matter_actor_lnk', 'role'));
    }

    public function test_tier4_classifier_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('classifier'));
        $this->assertTrue(Schema::hasColumn('classifier', 'id'));
        $this->assertTrue(Schema::hasColumn('classifier', 'matter_id'));
        $this->assertTrue(Schema::hasColumn('classifier', 'type_code'));
    }

    public function test_tier4_event_class_lnk_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('event_class_lnk'));
        $this->assertTrue(Schema::hasColumn('event_class_lnk', 'id'));
        $this->assertTrue(Schema::hasColumn('event_class_lnk', 'event_name_code'));
        $this->assertTrue(Schema::hasColumn('event_class_lnk', 'template_class_id'));
    }

    public function test_tier4_renewals_logs_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('renewals_logs'));
        $this->assertTrue(Schema::hasColumn('renewals_logs', 'id'));
        $this->assertTrue(Schema::hasColumn('renewals_logs', 'task_id'));
    }

    // =========================================================================
    // TIER 5: Laravel Standard Tables
    // =========================================================================

    public function test_tier5_migrations_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('migrations'));
    }

    public function test_tier5_password_resets_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('password_resets'));
    }

    public function test_tier5_failed_jobs_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('failed_jobs'));
    }

    // =========================================================================
    // TIER 6: Audit Table
    // =========================================================================

    public function test_tier6_audit_logs_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('audit_logs'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'id'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'auditable_type'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'auditable_id'));
    }

    // =========================================================================
    // VIEWS
    // =========================================================================

    public function test_users_view_exists(): void
    {
        $views = DB::select("SELECT viewname FROM pg_views WHERE schemaname = 'public' AND viewname = 'users'");
        $this->assertCount(1, $views, 'The users view should exist');
    }

    public function test_users_view_has_correct_columns(): void
    {
        $columns = DB::select("
            SELECT column_name FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = 'users'
            ORDER BY ordinal_position
        ");

        $columnNames = array_map(fn ($c) => $c->column_name, $columns);

        $this->assertContains('id', $columnNames);
        $this->assertContains('name', $columnNames);
        $this->assertContains('email', $columnNames);
        $this->assertContains('password', $columnNames);
        $this->assertContains('login', $columnNames);
    }

    public function test_event_lnk_list_view_exists(): void
    {
        $views = DB::select("SELECT viewname FROM pg_views WHERE schemaname = 'public' AND viewname = 'event_lnk_list'");
        $this->assertCount(1, $views, 'The event_lnk_list view should exist');
    }

    public function test_matter_actors_view_exists(): void
    {
        $views = DB::select("SELECT viewname FROM pg_views WHERE schemaname = 'public' AND viewname = 'matter_actors'");
        $this->assertCount(1, $views, 'The matter_actors view should exist');
    }

    public function test_matter_classifiers_view_exists(): void
    {
        $views = DB::select("SELECT viewname FROM pg_views WHERE schemaname = 'public' AND viewname = 'matter_classifiers'");
        $this->assertCount(1, $views, 'The matter_classifiers view should exist');
    }

    public function test_task_list_view_exists(): void
    {
        $views = DB::select("SELECT viewname FROM pg_views WHERE schemaname = 'public' AND viewname = 'task_list'");
        $this->assertCount(1, $views, 'The task_list view should exist');
    }

    // =========================================================================
    // STORED FUNCTIONS
    // =========================================================================

    public function test_tcase_function_exists(): void
    {
        $result = DB::select("SELECT proname FROM pg_proc WHERE proname = 'tcase'");
        $this->assertCount(1, $result, 'The tcase function should exist');
    }

    public function test_actor_list_function_exists(): void
    {
        $result = DB::select("SELECT proname FROM pg_proc WHERE proname = 'actor_list'");
        $this->assertCount(1, $result, 'The actor_list function should exist');
    }

    public function test_matter_status_function_exists(): void
    {
        $result = DB::select("SELECT proname FROM pg_proc WHERE proname = 'matter_status'");
        $this->assertCount(1, $result, 'The matter_status function should exist');
    }

    public function test_compute_matter_uid_function_exists(): void
    {
        $result = DB::select("SELECT proname FROM pg_proc WHERE proname = 'compute_matter_uid'");
        $this->assertCount(1, $result, 'The compute_matter_uid function should exist');
    }

    public function test_insert_recurring_renewals_function_exists(): void
    {
        $result = DB::select("SELECT proname FROM pg_proc WHERE proname = 'insert_recurring_renewals'");
        $this->assertCount(1, $result, 'The insert_recurring_renewals function should exist');
    }

    public function test_update_expired_function_exists(): void
    {
        $result = DB::select("SELECT proname FROM pg_proc WHERE proname = 'update_expired'");
        $this->assertCount(1, $result, 'The update_expired function should exist');
    }

    // =========================================================================
    // TRIGGERS
    // =========================================================================

    public function test_classifier_before_insert_trigger_exists(): void
    {
        $result = DB::select("
            SELECT trigger_name FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = 'classifier_before_insert'
        ");
        $this->assertCount(1, $result, 'The classifier_before_insert trigger should exist');
    }

    public function test_event_before_insert_trigger_exists(): void
    {
        $result = DB::select("
            SELECT trigger_name FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = 'event_before_insert'
        ");
        $this->assertCount(1, $result, 'The event_before_insert trigger should exist');
    }

    public function test_event_before_update_trigger_exists(): void
    {
        $result = DB::select("
            SELECT trigger_name FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = 'event_before_update'
        ");
        $this->assertCount(1, $result, 'The event_before_update trigger should exist');
    }

    public function test_matter_before_insert_trigger_exists(): void
    {
        $result = DB::select("
            SELECT trigger_name FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = 'matter_before_insert'
        ");
        $this->assertCount(1, $result, 'The matter_before_insert trigger should exist');
    }

    public function test_matter_before_update_trigger_exists(): void
    {
        $result = DB::select("
            SELECT trigger_name FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = 'matter_before_update'
        ");
        $this->assertCount(1, $result, 'The matter_before_update trigger should exist');
    }

    public function test_task_before_insert_trigger_exists(): void
    {
        $result = DB::select("
            SELECT trigger_name FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = 'task_before_insert'
        ");
        $this->assertCount(1, $result, 'The task_before_insert trigger should exist');
    }

    public function test_task_before_update_trigger_exists(): void
    {
        $result = DB::select("
            SELECT trigger_name FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = 'task_before_update'
        ");
        $this->assertCount(1, $result, 'The task_before_update trigger should exist');
    }

    // =========================================================================
    // SUMMARY TEST
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
