<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

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
