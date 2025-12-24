<?php

namespace Tests;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * Base test case for phpIP tests.
 *
 * IMPORTANT: This project uses a schema-dump approach, not full Laravel migrations.
 * The base database structure comes from `database/schema/postgres-schema.sql`,
 * and migrations are incremental changes on top of that.
 *
 * Tests use DatabaseTransactions (not RefreshDatabase) to:
 * 1. Wrap each test in a transaction
 * 2. Roll back after each test
 * 3. Avoid the need to rebuild the database from migrations
 *
 * Before running tests, ensure the test database exists and has the schema loaded:
 *   ./tests/setup-test-db.sh
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

    /**
     * @deprecated Use seedTestData() instead for better performance
     */
    public function resetDatabase()
    {
        $this->artisan('migrate:rollback');
        $this->artisan('migrate');
        $this->artisan('db:seed');
    }

    /**
     * @deprecated Use seedTestData() instead for better performance
     */
    public function resetDatabaseAndSeed()
    {
        $this->resetDatabase();
        $this->artisan('db:seed --class=SampleSeeder');
    }
}
