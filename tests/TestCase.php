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

    /**
     * Use the PostgreSQL schema dump when available to hydrate the base schema
     * before running migrations in tests.
     */
    protected function migrateFreshUsing()
    {
        $options = [
            '--drop-views' => property_exists($this, 'dropViews') ? $this->dropViews : false,
            '--drop-types' => property_exists($this, 'dropTypes') ? $this->dropTypes : false,
        ];

        $schemaPath = database_path('schema/postgres-schema.sql');
        if (config('database.default') === 'pgsql' && is_file($schemaPath)) {
            $options['--schema-path'] = $schemaPath;
        }

        if (property_exists($this, 'seeder') && $this->seeder) {
            $options['--seeder'] = $this->seeder;
        } else {
            $options['--seed'] = property_exists($this, 'seed') ? $this->seed : false;
        }

        return $options;
    }
}
