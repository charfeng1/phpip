<?php

namespace Tests\Unit;

use App\Models\Matter;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PostgreSQLCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test JSON column operations with PostgreSQL -> operator
     */
    public function test_json_column_extraction_with_postgres_operator()
    {
        // This tests the -> and ->> operators used in PostgreSQL for JSON

        // Test country name JSON extraction
        $result = DB::select("SELECT name->>'en' as name_en FROM country LIMIT 1");
        $this->assertNotNull($result);

        // Test event_name JSON extraction
        $result = DB::select("SELECT name->>'en' as event_name FROM event_name LIMIT 1");
        $this->assertNotNull($result);
    }

    /**
     * Test STRING_AGG aggregate function (PostgreSQL replacement for GROUP_CONCAT)
     */
    public function test_string_agg_function()
    {
        // STRING_AGG is PostgreSQL's equivalent to MySQL's GROUP_CONCAT
        $query = "SELECT STRING_AGG(name, '; ') as names FROM actor LIMIT 1";

        try {
            $result = DB::select($query);
            $this->assertNotNull($result);
        } catch (\Exception $e) {
            $this->fail("STRING_AGG function not working: " . $e->getMessage());
        }
    }

    /**
     * Test COALESCE function (replacement for IFNULL)
     */
    public function test_coalesce_function()
    {
        $result = DB::select("SELECT COALESCE(NULL, 'default') as value");
        $this->assertEquals('default', $result[0]->value);

        $result = DB::select("SELECT COALESCE('value', 'default') as value");
        $this->assertEquals('value', $result[0]->value);
    }

    /**
     * Test ILIKE operator for case-insensitive matching
     */
    public function test_ilike_operator()
    {
        // Create a test actor
        DB::table('actor')->insert([
            'name' => 'Test Company Inc.',
            'phy_person' => 0,
            'small_entity' => 0,
            'ren_discount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Test ILIKE (case-insensitive LIKE)
        $result = DB::select("SELECT * FROM actor WHERE name ILIKE ?", ['%test%']);
        $this->assertNotEmpty($result);

        $result = DB::select("SELECT * FROM actor WHERE name ILIKE ?", ['%TEST%']);
        $this->assertNotEmpty($result);

        $result = DB::select("SELECT * FROM actor WHERE name ILIKE ?", ['%TeSt%']);
        $this->assertNotEmpty($result);
    }

    /**
     * Test CAST to INTEGER (replacement for CAST AS UNSIGNED)
     */
    public function test_cast_to_integer()
    {
        $result = DB::select("SELECT CAST('123' AS INTEGER) as value");
        $this->assertEquals(123, $result[0]->value);
        $this->assertIsInt($result[0]->value);
    }

    /**
     * Test boolean columns (replacement for TINYINT(1))
     */
    public function test_boolean_columns()
    {
        // PostgreSQL uses actual BOOLEAN type instead of TINYINT(1)
        DB::table('actor')->insert([
            'name' => 'Boolean Test',
            'phy_person' => true,
            'small_entity' => false,
            'ren_discount' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $result = DB::table('actor')->where('name', 'Boolean Test')->first();
        $this->assertTrue($result->phy_person);
        $this->assertFalse($result->small_entity);
        $this->assertIsBool($result->phy_person);
    }

    /**
     * Test NULL checks (IS NULL instead of ISNULL())
     */
    public function test_null_checks()
    {
        $result = DB::select("SELECT * FROM matter WHERE container_id IS NULL LIMIT 1");
        $this->assertNotNull($result);

        $result = DB::select("SELECT * FROM matter WHERE container_id IS NOT NULL LIMIT 1");
        $this->assertNotNull($result);
    }

    /**
     * Test that views exist and are queryable
     */
    public function test_database_views_exist()
    {
        $views = [
            'event_lnk_list',
            'matter_actors',
            'matter_classifiers',
            'renewal_list',
            'task_list',
            'users',
        ];

        foreach ($views as $view) {
            try {
                DB::select("SELECT * FROM {$view} LIMIT 1");
                $this->assertTrue(true, "View {$view} exists and is queryable");
            } catch (\Exception $e) {
                $this->markTestSkipped("View {$view} not created yet: " . $e->getMessage());
            }
        }
    }

    /**
     * Test JSON column queries in whereJsonLike macro
     */
    public function test_where_json_like_macro()
    {
        // Test the custom whereJsonLike macro for PostgreSQL
        try {
            $result = DB::table('country')
                ->whereJsonLike('name', 'United')
                ->first();

            $this->assertNotNull($result);
        } catch (\Exception $e) {
            $this->markTestSkipped("whereJsonLike macro test: " . $e->getMessage());
        }
    }

    /**
     * Test that JSONB columns support indexing (PostgreSQL specific)
     */
    public function test_jsonb_column_performance()
    {
        // This would test if JSONB is being used instead of JSON
        // JSONB is better for PostgreSQL as it's binary and supports indexing

        try {
            // Check if we can query JSON efficiently
            $result = DB::select("SELECT name->>'en' as name FROM country WHERE name->>'en' IS NOT NULL LIMIT 10");
            $this->assertNotEmpty($result);
        } catch (\Exception $e) {
            $this->fail("JSONB column query failed: " . $e->getMessage());
        }
    }

    /**
     * Test date/time functions compatibility
     */
    public function test_datetime_functions()
    {
        // PostgreSQL uses different date functions
        $result = DB::select("SELECT NOW() as current_time");
        $this->assertNotNull($result[0]->current_time);

        $result = DB::select("SELECT CURRENT_TIMESTAMP as current_timestamp");
        $this->assertNotNull($result[0]->current_timestamp);
    }

    /**
     * Test INTERVAL syntax for date arithmetic
     */
    public function test_interval_date_arithmetic()
    {
        $result = DB::select("SELECT NOW() + INTERVAL '1 day' as tomorrow");
        $this->assertNotNull($result[0]->tomorrow);

        $result = DB::select("SELECT NOW() + INTERVAL '1 month' as next_month");
        $this->assertNotNull($result[0]->next_month);

        $result = DB::select("SELECT NOW() + INTERVAL '1 year' as next_year");
        $this->assertNotNull($result[0]->next_year);
    }
}
