# Testing Guide for Supabase Migration

This document provides comprehensive testing instructions for the MySQL to Supabase (PostgreSQL) migration.

## Overview

The test suite includes:
- **Unit Tests**: Test individual models and PostgreSQL compatibility
- **Feature Tests**: Test controllers and HTTP endpoints
- **Integration Tests**: Test database triggers and stored procedures
- **Compatibility Tests**: Ensure PostgreSQL-specific queries work correctly

## Test Structure

```
tests/
├── Unit/
│   ├── PostgreSQLCompatibilityTest.php  - Core PostgreSQL feature tests
│   ├── TaskModelTest.php                 - Task model PostgreSQL queries
│   └── MatterModelTest.php               - Matter model PostgreSQL queries
├── Feature/
│   ├── MatterControllerTest.php          - Matter CRUD operations
│   ├── CountryControllerTest.php         - Country with JSON columns
│   ├── AutocompleteControllerTest.php    - All autocomplete endpoints
│   ├── DatabaseTriggersTest.php          - Trigger functionality (requires DB setup)
│   ├── ActorControllerTest.php           - Existing actor tests
│   ├── HomeControllerTest.php            - Existing home tests
│   └── RuleControllerTest.php            - Existing rule tests
└── TestCase.php                          - Base test class
```

## Prerequisites

### 1. Environment Setup

Create a `.env.testing` file for test database:

```env
APP_ENV=testing
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=phpip_test
DB_USERNAME=postgres
DB_PASSWORD=your-test-password
DB_SSLMODE=prefer

CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_DRIVER=sync
MAIL_DRIVER=array
```

### 2. Test Database Setup

Create a test database in PostgreSQL:

```bash
# Connect to PostgreSQL
psql -U postgres

# Create test database
CREATE DATABASE phpip_test;

# Grant permissions
GRANT ALL PRIVILEGES ON DATABASE phpip_test TO postgres;
```

### 3. Install Database Schema

Before running tests, you **MUST** set up the PostgreSQL database schema:

1. **Create all tables** (see `DATABASE_OBJECTS.md`)
2. **Create all views** (6 views)
3. **Create all triggers** (15 triggers)
4. **Create stored procedures** (2 procedures)
5. **Create stored functions** (1 function)

## Running Tests

### Run All Tests

```bash
php artisan test
```

### Run Specific Test Suites

```bash
# Run only unit tests
php artisan test --testsuite=Unit

# Run only feature tests
php artisan test --testsuite=Feature

# Run specific test class
php artisan test tests/Unit/PostgreSQLCompatibilityTest.php

# Run specific test method
php artisan test --filter test_json_column_extraction_with_postgres_operator
```

### Run With Coverage

```bash
php artisan test --coverage
```

## Test Categories

### 1. PostgreSQL Compatibility Tests ⚠️ **RUN THESE FIRST**

File: `tests/Unit/PostgreSQLCompatibilityTest.php`

**Purpose**: Verify core PostgreSQL features work

**Tests**:
- ✅ JSON column extraction (`->` and `->>` operators)
- ✅ `STRING_AGG()` function (replaces `GROUP_CONCAT`)
- ✅ `COALESCE()` function (replaces `IFNULL`)
- ✅ `ILIKE` operator (case-insensitive matching)
- ✅ `CAST AS INTEGER` (replaces `CAST AS UNSIGNED`)
- ✅ Boolean columns (vs `TINYINT(1)`)
- ✅ NULL checks (`IS NULL` vs `ISNULL()`)
- ✅ Database views exist
- ✅ `whereJsonLike` macro
- ✅ Date/time functions
- ✅ `INTERVAL` syntax

**Run**:
```bash
php artisan test tests/Unit/PostgreSQLCompatibilityTest.php
```

### 2. Model Tests

**TaskModelTest.php**:
- Task model PostgreSQL queries
- JSON detail column operations
- CAST to INTEGER for renewal years
- User counts with COALESCE

**MatterModelTest.php**:
- Matter filter with PostgreSQL syntax
- STRING_AGG in queries
- COALESCE in complex joins
- JSON extraction for country names
- Relationship loading
- Boolean container checks

**Run**:
```bash
php artisan test tests/Unit/TaskModelTest.php
php artisan test tests/Unit/MatterModelTest.php
```

### 3. Controller Feature Tests

**MatterControllerTest.php**:
- Index page with PostgreSQL filtering
- Matter CRUD operations
- ILIKE phonetic matching (replaces SOUNDS LIKE)
- Export functionality
- JSON API responses
- Relationships (events, tasks, actors)

**CountryControllerTest.php**:
- JSON name column queries
- ILIKE case-insensitive search
- whereJsonLike macro usage
- Autocomplete with JSON

**AutocompleteControllerTest.php**:
- All autocomplete endpoints
- JSON column autocomplete
- whereLike operations
- COALESCE in queries

**Run**:
```bash
php artisan test tests/Feature/MatterControllerTest.php
php artisan test tests/Feature/CountryControllerTest.php
php artisan test tests/Feature/AutocompleteControllerTest.php
```

### 4. Database Trigger Tests ⚠️ **REQUIRES DB SETUP**

File: `tests/Feature/DatabaseTriggersTest.php`

**⚠️ IMPORTANT**: These tests are SKIPPED by default because they require database triggers to be created first.

**After creating all triggers**, remove the `markTestSkipped()` calls to enable these tests.

**Tests**:
- `classifier_before_insert` trigger (title case formatting)
- `event_after_insert` trigger (**CRITICAL** - generates tasks)
- `matter_after_insert` trigger (creates CRE event, assigns actors)
- `task_before_insert` trigger (default assignments)
- `task_before_update` trigger (done/done_date management)
- `tcase()` function
- `insert_recurring_renewals()` procedure
- `recalculate_tasks()` procedure
- Full workflow integration test

**Run** (after DB setup):
```bash
php artisan test tests/Feature/DatabaseTriggersTest.php
```

## Test Execution Order

### Phase 1: Basic Compatibility ✅

1. **PostgreSQL Compatibility Test**
   ```bash
   php artisan test tests/Unit/PostgreSQLCompatibilityTest.php
   ```
   **Expected**: All tests pass
   **If failing**: Database connection or schema issues

### Phase 2: Model Tests ✅

2. **Model Tests**
   ```bash
   php artisan test tests/Unit/TaskModelTest.php
   php artisan test tests/Unit/MatterModelTest.php
   ```
   **Expected**: Most tests pass or skip gracefully
   **If failing**: SQL syntax issues in models

### Phase 3: Controller Tests ✅

3. **Controller Feature Tests**
   ```bash
   php artisan test tests/Feature/
   ```
   **Expected**: HTTP tests pass
   **If failing**: Routing or controller issues

### Phase 4: Database Objects ⚠️ **MANUAL SETUP REQUIRED**

4. **Trigger Tests** (after creating all database objects)
   ```bash
   php artisan test tests/Feature/DatabaseTriggersTest.php
   ```
   **Expected**: Currently skipped
   **To enable**: Remove `markTestSkipped()` after creating triggers

## Common Test Failures and Solutions

### Connection Refused

**Error**: `Connection refused`

**Solution**:
- Check PostgreSQL is running
- Verify connection details in `.env.testing`
- Ensure test database exists

### Relation Does Not Exist

**Error**: `relation "table_name" does not exist`

**Solution**:
- Run migrations: `php artisan migrate --env=testing`
- Ensure test database has schema
- Check `DB_DATABASE` in `.env.testing`

### Unknown Column

**Error**: `column "name" does not exist`

**Solution**:
- Database schema not fully created
- Missing table columns
- Run migrations again

### Function Does Not Exist

**Error**: `function string_agg() does not exist`

**Solution**:
- Using MySQL instead of PostgreSQL
- Check `DB_CONNECTION=pgsql` in `.env.testing`

### View Does Not Exist

**Error**: `relation "view_name" does not exist`

**Solution**:
- Database views not created
- See `DATABASE_OBJECTS.md` for view definitions
- Views must be created manually in PostgreSQL

### Trigger Tests Skipped

**Status**: `S` (Skipped)

**Solution**:
- This is expected until triggers are created
- Create all database triggers as per `DATABASE_OBJECTS.md`
- Remove `markTestSkipped()` in `DatabaseTriggersTest.php`

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      postgres:
        image: postgres:15
        env:
          POSTGRES_USER: postgres
          POSTGRES_PASSWORD: postgres
          POSTGRES_DB: phpip_test
        ports:
          - 5432:5432
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
          --health-timeout 5s
          --health-retries 5

    steps:
      - uses: actions/checkout@v2

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          extensions: pdo, pdo_pgsql

      - name: Install Dependencies
        run: composer install

      - name: Create Database Schema
        run: |
          # Import schema (you'll need to add schema import here)
          php artisan migrate --env=testing

      - name: Run Tests
        run: php artisan test
        env:
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_PORT: 5432
          DB_DATABASE: phpip_test
          DB_USERNAME: postgres
          DB_PASSWORD: postgres
```

## Test Coverage Goals

| Component | Coverage Goal |
|-----------|---------------|
| Models | 80%+ |
| Controllers | 75%+ |
| API Endpoints | 90%+ |
| Database Queries | 100% |
| Triggers | 100% (after setup) |

## Writing New Tests

### For New Controllers

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_index()
    {
        $response = $this->get('/your-route');
        $response->assertStatus(200);
    }
}
```

### For New Models

```php
<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NewModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_postgres_query()
    {
        // Test PostgreSQL-specific features
        $this->assertNotNull(YourModel::all());
    }
}
```

## Debugging Tests

### Enable Query Logging

```php
DB::enableQueryLog();

// Your test code

dd(DB::getQueryLog());
```

### Verbose Output

```bash
php artisan test --verbose
```

### Stop on Failure

```bash
php artisan test --stop-on-failure
```

### Run Single Test with Debug

```bash
php artisan test --filter test_name --verbose
```

## Performance Testing

### Database Query Performance

```php
public function test_query_performance()
{
    $start = microtime(true);

    Matter::filter('id', 'desc', [], null, false)->take(100)->get();

    $time = microtime(true) - $start;

    $this->assertLessThan(1.0, $time, 'Query should complete in under 1 second');
}
```

## Summary

✅ **Application Code Tests**: Ready to run (may have some skips until DB setup)
⚠️ **Database Trigger Tests**: Require manual database object creation
📚 **Documentation**: See `DATABASE_OBJECTS.md` for trigger/procedure creation

**Next Steps**:
1. Run PostgreSQL compatibility tests
2. Run model and controller tests
3. Create database objects (triggers, procedures, functions)
4. Enable and run trigger tests
5. Verify full integration

**All tests passing = Migration successful!**
