<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Minimal seeder for test suite.
 *
 * Only seeds essential reference data needed for tests to run.
 * This avoids the performance penalty of running the full DatabaseSeeder
 * for every single test (324 times).
 */
class TestSeeder extends Seeder
{
    public function run()
    {
        // Only seed critical reference tables that tests rely on
        $this->call([
            CountryTableSeeder::class,
            MatterCategoryTableSeeder::class,
            ActorRoleTableSeeder::class,
        ]);
    }
}
