<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base test case for phpIP tests.
 *
 * Database Setup Options:
 *
 * 1. RefreshDatabase (full reset each test):
 *    - Use when testing migrations or needing a completely clean slate
 *    - Slower but guarantees isolation
 *    - Example: `use RefreshDatabase;` in your test class
 *
 * 2. DatabaseTransactions (default, faster):
 *    - Wraps each test in a transaction, rolls back after
 *    - Faster for most tests
 *    - Inherited from this base class
 *
 * The baseline migration (0001_01_01_000001_create_baseline_schema) loads
 * the complete schema from postgres-schema.sql, so `php artisan migrate:fresh`
 * now works correctly.
 *
 * Quick start:
 *   php artisan migrate:fresh --env=testing --seed
 *   php artisan test
 */
abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseTransactions;

    /**
     * The database connection to use for transactions.
     */
    protected $connectionsToTransact = ['pgsql'];

    protected $dropViews = true;

    /**
     * Seed only essential reference data for tests.
     * Use this instead of full db:seed for better performance.
     */
    protected function seedTestData(): void
    {
        $this->artisan('db:seed --class=TestSeeder');
    }
}
