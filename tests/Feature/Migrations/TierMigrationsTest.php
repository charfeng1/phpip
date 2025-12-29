<?php

namespace Tests\Feature\Migrations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Tests for the individual tier migrations.
 *
 * These tests verify that each tier migration creates the correct
 * database objects with proper structure. The tests complement
 * BaselineMigrationTest by testing the modular migration approach.
 *
 * Note: These tests use RefreshDatabase which runs all migrations,
 * so they verify the cumulative result of all tier migrations.
 */
class TierMigrationsTest extends TestCase
{
    use RefreshDatabase;

    protected $seed = true;

    // =========================================================================
    // TIER 1: Foundation Tables
    // =========================================================================

    /**
     * @dataProvider tier1TablesProvider
     */
    public function test_tier1_tables_exist(string $table, array $columns): void
    {
        $this->assertTrue(Schema::hasTable($table), "Table '{$table}' should exist");

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn($table, $column),
                "Table '{$table}' should have column '{$column}'"
            );
        }
    }

    public static function tier1TablesProvider(): array
    {
        return [
            'country' => ['country', ['iso', 'name', 'ep', 'wo', 'renewal_first', 'renewal_base']],
            'actor_role' => ['actor_role', ['code', 'name', 'shareable', 'display_order']],
            'matter_category' => ['matter_category', ['code', 'category', 'ref_prefix']],
            'matter_type' => ['matter_type', ['code', 'type']],
            'event_name' => ['event_name', ['code', 'name', 'is_task', 'killer']],
            'classifier_type' => ['classifier_type', ['code', 'type', 'main_display']],
            'template_classes' => ['template_classes', ['id', 'name']],
        ];
    }

    // =========================================================================
    // TIER 2: First-Level FK Tables
    // =========================================================================

    /**
     * @dataProvider tier2TablesProvider
     */
    public function test_tier2_tables_exist(string $table, array $columns): void
    {
        $this->assertTrue(Schema::hasTable($table), "Table '{$table}' should exist");

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn($table, $column),
                "Table '{$table}' should have column '{$column}'"
            );
        }
    }

    public static function tier2TablesProvider(): array
    {
        return [
            'actor' => ['actor', ['id', 'name', 'login', 'password', 'default_role', 'parent_id']],
            'classifier_value' => ['classifier_value', ['id', 'value', 'type_code']],
            'template_members' => ['template_members', ['id', 'class_id', 'body']],
            'fees' => ['fees', ['id', 'for_country', 'for_category', 'qt', 'cost', 'fee']],
            'default_actor' => ['default_actor', ['id', 'actor_id', 'role', 'for_category']],
        ];
    }

    // =========================================================================
    // TIER 3: Business Core Tables
    // =========================================================================

    /**
     * @dataProvider tier3TablesProvider
     */
    public function test_tier3_tables_exist(string $table, array $columns): void
    {
        $this->assertTrue(Schema::hasTable($table), "Table '{$table}' should exist");

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn($table, $column),
                "Table '{$table}' should have column '{$column}'"
            );
        }
    }

    public static function tier3TablesProvider(): array
    {
        return [
            'matter' => ['matter', ['id', 'caseref', 'uid', 'category_code', 'country', 'parent_id']],
            'event' => ['event', ['id', 'matter_id', 'code', 'event_date', 'alt_matter_id']],
            'task_rules' => ['task_rules', ['id', 'task', 'trigger_event', 'days', 'months', 'years']],
            'task' => ['task', ['id', 'trigger_id', 'code', 'due_date', 'done', 'done_date']],
        ];
    }

    // =========================================================================
    // TIER 4: Relationship Tables
    // =========================================================================

    /**
     * @dataProvider tier4TablesProvider
     */
    public function test_tier4_tables_exist(string $table, array $columns): void
    {
        $this->assertTrue(Schema::hasTable($table), "Table '{$table}' should exist");

        foreach ($columns as $column) {
            $this->assertTrue(
                Schema::hasColumn($table, $column),
                "Table '{$table}' should have column '{$column}'"
            );
        }
    }

    public static function tier4TablesProvider(): array
    {
        return [
            'matter_actor_lnk' => ['matter_actor_lnk', ['id', 'matter_id', 'actor_id', 'role', 'shared']],
            'classifier' => ['classifier', ['id', 'matter_id', 'type_code', 'value', 'value_id']],
            'event_class_lnk' => ['event_class_lnk', ['id', 'event_name_code', 'template_class_id']],
            'renewals_logs' => ['renewals_logs', ['id', 'task_id', 'from_step', 'to_step']],
        ];
    }

    // =========================================================================
    // TIER 5 & 6: Laravel Standard and Audit Tables
    // =========================================================================

    public function test_tier5_password_resets_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('password_resets'));
        $this->assertTrue(Schema::hasColumn('password_resets', 'email'));
        $this->assertTrue(Schema::hasColumn('password_resets', 'token'));
    }

    public function test_tier5_failed_jobs_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('failed_jobs'));
        $this->assertTrue(Schema::hasColumn('failed_jobs', 'uuid'));
        $this->assertTrue(Schema::hasColumn('failed_jobs', 'payload'));
    }

    public function test_tier5_jobs_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('jobs'));
        $this->assertTrue(Schema::hasColumn('jobs', 'queue'));
        $this->assertTrue(Schema::hasColumn('jobs', 'payload'));
        $this->assertTrue(Schema::hasColumn('jobs', 'attempts'));
        $this->assertTrue(Schema::hasColumn('jobs', 'reserved_at'));
        $this->assertTrue(Schema::hasColumn('jobs', 'available_at'));
        $this->assertTrue(Schema::hasColumn('jobs', 'created_at'));
    }

    public function test_tier6_audit_logs_table_exists(): void
    {
        $this->assertTrue(Schema::hasTable('audit_logs'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'auditable_type'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'auditable_id'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'action'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'old_values'));
        $this->assertTrue(Schema::hasColumn('audit_logs', 'new_values'));
    }

    // =========================================================================
    // STORED FUNCTIONS
    // =========================================================================

    /**
     * @dataProvider storedFunctionsProvider
     */
    public function test_stored_functions_exist(string $functionName): void
    {
        $result = DB::select("SELECT proname FROM pg_proc WHERE proname = ?", [$functionName]);
        $this->assertNotEmpty($result, "Function '{$functionName}' should exist");
    }

    public static function storedFunctionsProvider(): array
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
    // VIEWS
    // =========================================================================

    /**
     * @dataProvider viewsProvider
     */
    public function test_views_exist(string $viewName): void
    {
        $result = DB::select(
            "SELECT viewname FROM pg_views WHERE schemaname = 'public' AND viewname = ?",
            [$viewName]
        );
        $this->assertNotEmpty($result, "View '{$viewName}' should exist");
    }

    public static function viewsProvider(): array
    {
        return [
            'users' => ['users'],
            'event_lnk_list' => ['event_lnk_list'],
            'matter_actors' => ['matter_actors'],
            'matter_classifiers' => ['matter_classifiers'],
            'task_list' => ['task_list'],
        ];
    }

    public function test_users_view_has_required_columns(): void
    {
        $columns = DB::select("
            SELECT column_name FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = 'users'
        ");
        $columnNames = array_map(fn ($c) => $c->column_name, $columns);

        $requiredColumns = ['id', 'name', 'login', 'email', 'password', 'remember_token'];
        foreach ($requiredColumns as $column) {
            $this->assertContains($column, $columnNames, "Users view should have column '{$column}'");
        }
    }

    // =========================================================================
    // TRIGGERS
    // =========================================================================

    /**
     * @dataProvider triggersProvider
     */
    public function test_triggers_exist(string $triggerName): void
    {
        $result = DB::select("
            SELECT trigger_name FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = ?
        ", [$triggerName]);
        $this->assertNotEmpty($result, "Trigger '{$triggerName}' should exist");
    }

    public static function triggersProvider(): array
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

    /**
     * @dataProvider triggerFunctionsProvider
     */
    public function test_trigger_functions_exist(string $functionName): void
    {
        $result = DB::select("SELECT proname FROM pg_proc WHERE proname = ?", [$functionName]);
        $this->assertNotEmpty($result, "Trigger function '{$functionName}' should exist");
    }

    public static function triggerFunctionsProvider(): array
    {
        return [
            'classifier_before_insert_func' => ['classifier_before_insert_func'],
            'event_before_insert_func' => ['event_before_insert_func'],
            'event_before_update_func' => ['event_before_update_func'],
            'matter_before_insert_func' => ['matter_before_insert_func'],
            'matter_before_update_func' => ['matter_before_update_func'],
            'task_before_insert_func' => ['task_before_insert_func'],
            'task_before_update_func' => ['task_before_update_func'],
        ];
    }

    // =========================================================================
    // FOREIGN KEY CONSTRAINTS
    // =========================================================================

    public function test_tier2_actor_has_foreign_keys(): void
    {
        $fks = $this->getForeignKeys('actor');

        $this->assertContains('default_role', $fks, 'Actor should have FK to actor_role');
        $this->assertContains('country', $fks, 'Actor should have FK to country');
    }

    public function test_tier3_matter_has_foreign_keys(): void
    {
        $fks = $this->getForeignKeys('matter');

        $this->assertContains('category_code', $fks, 'Matter should have FK to matter_category');
        $this->assertContains('country', $fks, 'Matter should have FK to country');
    }

    public function test_tier3_event_has_foreign_keys(): void
    {
        $fks = $this->getForeignKeys('event');

        $this->assertContains('code', $fks, 'Event should have FK to event_name');
        $this->assertContains('matter_id', $fks, 'Event should have FK to matter');
    }

    public function test_tier4_matter_actor_lnk_has_foreign_keys(): void
    {
        $fks = $this->getForeignKeys('matter_actor_lnk');

        $this->assertContains('matter_id', $fks, 'MatterActorLnk should have FK to matter');
        $this->assertContains('actor_id', $fks, 'MatterActorLnk should have FK to actor');
        $this->assertContains('role', $fks, 'MatterActorLnk should have FK to actor_role');
    }

    /**
     * Get foreign key column names for a table.
     */
    private function getForeignKeys(string $table): array
    {
        $result = DB::select("
            SELECT kcu.column_name
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
                ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
            WHERE tc.constraint_type = 'FOREIGN KEY'
            AND tc.table_name = ?
            AND tc.table_schema = 'public'
        ", [$table]);

        return array_map(fn ($r) => $r->column_name, $result);
    }

    // =========================================================================
    // UNIQUE CONSTRAINTS
    // =========================================================================

    public function test_matter_has_unique_constraint(): void
    {
        $indexes = $this->getIndexNames('matter');
        $this->assertContains('uqmatter', $indexes, 'Matter should have unique index uqmatter');
    }

    public function test_matter_actor_lnk_has_unique_constraint(): void
    {
        $indexes = $this->getIndexNames('matter_actor_lnk');
        $this->assertContains('uqactor_link', $indexes, 'MatterActorLnk should have unique index uqactor_link');
    }

    public function test_fees_has_unique_constraint(): void
    {
        $indexes = $this->getIndexNames('fees');
        $this->assertContains('uqfees', $indexes, 'Fees should have unique index uqfees');
    }

    /**
     * Get index names for a table.
     */
    private function getIndexNames(string $table): array
    {
        $result = DB::select("
            SELECT indexname FROM pg_indexes
            WHERE schemaname = 'public' AND tablename = ?
        ", [$table]);

        return array_map(fn ($r) => $r->indexname, $result);
    }

    // =========================================================================
    // JSONB COLUMNS
    // =========================================================================

    public function test_translatable_columns_are_jsonb(): void
    {
        $jsonbColumns = [
            'country' => 'name',
            'actor_role' => 'name',
            'matter_category' => 'category',
            'matter_type' => 'type',
            'event_name' => 'name',
            'classifier_type' => 'type',
        ];

        foreach ($jsonbColumns as $table => $column) {
            $result = DB::select("
                SELECT data_type FROM information_schema.columns
                WHERE table_schema = 'public'
                AND table_name = ?
                AND column_name = ?
            ", [$table, $column]);

            $this->assertNotEmpty($result, "Column '{$table}.{$column}' should exist");
            $this->assertEquals(
                'jsonb',
                $result[0]->data_type,
                "Column '{$table}.{$column}' should be JSONB type"
            );
        }
    }
}
