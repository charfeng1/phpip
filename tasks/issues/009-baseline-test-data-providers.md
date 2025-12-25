# Issue 009: Refactor BaselineMigrationTest to Use Data Providers

## Problem Statement

The `BaselineMigrationTest` contains **42 repetitive test methods** that follow the same pattern but test different database objects. This creates:

1. **Code duplication** - Each test method follows the same assertion pattern
2. **Maintenance overhead** - Adding new database objects requires creating new test methods
3. **Reduced readability** - Hundreds of lines of nearly identical code
4. **Difficulty in modification** - Changes to test logic require updating 42 methods

## Current State

File: `tests/Feature/Migrations/BaselineMigrationTest.php` (487 lines)

| Test Type | Methods | Lines | Description |
|-----------|---------|-------|-------------|
| Table existence | 24 | 33-236 | Tests for 24 tables across 6 dependency tiers |
| View existence | 5 | 242-287 | Tests for 5 database views |
| Function existence | 6 | 293-327 | Tests for 6 stored functions |
| Trigger existence | 7 | 333-394 | Tests for 7 database triggers |
| **Total repetitive** | **42** | **~361** | Could be consolidated to ~100 lines |

### Example of Repetitive Code

**Current approach (24 table tests):**
```php
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
// ... 22 more similar methods
```

**Current approach (7 trigger tests):**
```php
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
// ... 5 more similar methods
```

## Proposed Solution

Use **PHPUnit data providers** to consolidate repetitive tests into data-driven test methods.

### Benefits

1. **Reduced code** - From ~361 lines to ~100 lines (~72% reduction)
2. **Easier maintenance** - Add new database objects by adding one entry to data provider
3. **Better readability** - Clear separation between test logic and test data
4. **Consistent testing** - All objects tested with identical assertion logic
5. **Easier debugging** - One location to fix test logic issues

### Implementation Strategy

#### 1. Consolidate Table Tests with Column Verification

```php
/**
 * @dataProvider tableStructureProvider
 */
public function test_tables_have_correct_structure(string $table, array $columns): void
{
    $this->assertTrue(Schema::hasTable($table), "Table '{$table}' should exist");

    foreach ($columns as $column) {
        $this->assertTrue(
            Schema::hasColumn($table, $column),
            "Table '{$table}' should have column '{$column}'"
        );
    }
}

public static function tableStructureProvider(): array
{
    return [
        // Tier 1: Foundation Tables
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

        // Tier 3: Business Core
        'matter' => ['matter', ['id', 'caseref', 'uid', 'category_code', 'country']],
        'event' => ['event', ['id', 'matter_id', 'code', 'event_date']],
        'task_rules' => ['task_rules', ['id', 'task', 'trigger_event']],
        'task' => ['task', ['id', 'trigger_id', 'code', 'due_date']],

        // Tier 4: Relationship Tables
        'matter_actor_lnk' => ['matter_actor_lnk', ['id', 'matter_id', 'actor_id', 'role']],
        'classifier' => ['classifier', ['id', 'matter_id', 'type_code']],
        'event_class_lnk' => ['event_class_lnk', ['id', 'event_name_code', 'template_class_id']],
        'renewals_logs' => ['renewals_logs', ['id', 'task_id']],

        // Tier 5: Laravel Standard Tables
        'migrations' => ['migrations', []], // No specific columns to check
        'password_resets' => ['password_resets', []],
        'failed_jobs' => ['failed_jobs', []],

        // Tier 6: Audit Table
        'audit_logs' => ['audit_logs', ['id', 'auditable_type', 'auditable_id']],
    ];
}
```

#### 2. Consolidate View Existence Tests

```php
/**
 * @dataProvider viewProvider
 */
public function test_views_exist(string $view, array $expectedColumns = []): void
{
    $views = DB::select("SELECT viewname FROM pg_views WHERE schemaname = 'public' AND viewname = ?", [$view]);
    $this->assertCount(1, $views, "The '{$view}' view should exist");

    // Optionally verify columns if provided (e.g., for critical views like 'users')
    if (!empty($expectedColumns)) {
        $columns = DB::select("
            SELECT column_name FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = ?
            ORDER BY ordinal_position
        ", [$view]);

        $columnNames = array_map(fn ($c) => $c->column_name, $columns);

        foreach ($expectedColumns as $column) {
            $this->assertContains($column, $columnNames, "View '{$view}' should have column '{$column}'");
        }
    }
}

public static function viewProvider(): array
{
    return [
        'users_view' => ['users', ['id', 'name', 'email', 'password', 'login']],
        'event_lnk_list_view' => ['event_lnk_list', []],
        'matter_actors_view' => ['matter_actors', []],
        'matter_classifiers_view' => ['matter_classifiers', []],
        'task_list_view' => ['task_list', []],
    ];
}
```

#### 3. Consolidate Function Existence Tests

```php
/**
 * @dataProvider functionProvider
 */
public function test_functions_exist(string $functionName): void
{
    $result = DB::select("SELECT proname FROM pg_proc WHERE proname = ?", [$functionName]);
    $this->assertCount(1, $result, "The '{$functionName}' function should exist");
}

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
```

#### 4. Consolidate Trigger Existence Tests

```php
/**
 * @dataProvider triggerProvider
 */
public function test_triggers_exist(string $triggerName): void
{
    $result = DB::select("
        SELECT trigger_name FROM information_schema.triggers
        WHERE trigger_schema = 'public' AND trigger_name = ?
    ", [$triggerName]);

    $this->assertCount(1, $result, "The '{$triggerName}' trigger should exist");
}

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
```

### Summary Tests

The existing summary tests (`test_all_expected_*`) should be kept as-is, as they provide:
- Regression protection against schema additions/removals
- Quick verification of object counts
- Different testing approach (aggregate checks vs individual checks)

## Implementation Tasks

- [ ] **Task 1.1**: Create `tableStructureProvider` and refactor table tests
  - Consolidate 24 table test methods into 1 data-driven test
  - Maintain all existing column checks
  - Preserve tier organization via provider array structure

- [ ] **Task 1.2**: Create `viewProvider` and refactor view tests
  - Consolidate 5 view test methods into 1 data-driven test
  - Preserve column checks for critical 'users' view

- [ ] **Task 1.3**: Create `functionProvider` and refactor function tests
  - Consolidate 6 function test methods into 1 data-driven test

- [ ] **Task 1.4**: Create `triggerProvider` and refactor trigger tests
  - Consolidate 7 trigger test methods into 1 data-driven test

- [ ] **Task 1.5**: Run all tests and verify behavior matches original
  - Ensure all 42 original tests are covered
  - Verify no regression in test coverage
  - Check that test failure messages remain informative

- [ ] **Task 1.6**: Update documentation
  - Add PHPDoc comments to data provider methods
  - Document the data provider approach for future contributors

## Impact Assessment

### Risk Level: **LOW**

- **No functional changes** - Only refactoring test implementation
- **All existing assertions preserved** - Same checks, different structure
- **Easy rollback** - Can revert to original approach if issues arise
- **PHPUnit data providers are stable** - Well-established pattern

### Benefits:

| Metric | Before | After | Improvement |
|--------|--------|-------|-------------|
| Test methods | 42 | 4 | 90% reduction |
| Lines of code | ~361 | ~100 | 72% reduction |
| Maintenance cost | High | Low | Single place to update |
| Code clarity | Low | High | Clear separation of data/logic |

## Success Criteria

1. ✅ All 42 original test cases covered by new data provider tests
2. ✅ All tests pass (0 failures, 0 errors)
3. ✅ Test coverage remains 100% for baseline migration
4. ✅ Code reduced by at least 65% (target: 72%)
5. ✅ Failure messages remain informative and helpful
6. ✅ Summary tests continue to work as before

## Notes

- **Do not remove summary tests** - They provide aggregate verification that complements individual tests
- **Maintain provider array structure** - Keep tier organization and clear grouping
- **Use parameterized queries** - Prevent SQL injection in dynamic queries (shown in examples)
- **Preserve test naming** - Use descriptive provider keys for clear test output (e.g., 'country', 'actor')

## References

- PHPUnit Data Providers: https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html#writing-tests-for-phpunit.data-providers
- Original PR: charfeng1/phpip#32 (baseline migration tests)
- Test file: `tests/Feature/Migrations/BaselineMigrationTest.php`
- Review feedback: Kilo review suggestion for data providers (Issue: 2025-12-25)
