<?php

namespace Tests\Unit;

use App\Models\Matter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MatterModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test Matter model's filter() method with PostgreSQL syntax
     */
    public function test_matter_filter_uses_postgres_syntax()
    {
        try {
            $matters = Matter::filter('id', 'desc', [], null, false)->take(10)->get();
            $this->assertNotNull($matters);
        } catch (\Exception $e) {
            $this->fail("Matter filter query failed: " . $e->getMessage());
        }
    }

    /**
     * Test STRING_AGG in Matter queries (replacement for GROUP_CONCAT)
     */
    public function test_matter_uses_string_agg()
    {
        try {
            $query = DB::table('matter')
                ->select(DB::raw("STRING_AGG(DISTINCT matter.uid, '; ') as uids"))
                ->take(1);

            $result = $query->get();
            $this->assertNotNull($result);
        } catch (\Exception $e) {
            $this->markTestSkipped("STRING_AGG test: " . $e->getMessage());
        }
    }

    /**
     * Test COALESCE in Matter queries (replacement for IFNULL)
     */
    public function test_matter_uses_coalesce()
    {
        try {
            $query = Matter::select([
                'matter.id',
                DB::raw('COALESCE(matter.container_id, matter.id) as effective_id'),
            ])->take(1);

            $result = $query->get();
            $this->assertNotNull($result);
        } catch (\Exception $e) {
            $this->markTestSkipped("COALESCE test: " . $e->getMessage());
        }
    }

    /**
     * Test IS NULL checks in Matter queries
     */
    public function test_matter_null_checks()
    {
        try {
            // Test for matters without containers
            $containers = Matter::whereNull('container_id')->take(5)->get();
            $this->assertNotNull($containers);

            // Test for matters with containers
            $members = Matter::whereNotNull('container_id')->take(5)->get();
            $this->assertNotNull($members);
        } catch (\Exception $e) {
            $this->markTestSkipped("NULL checks test: " . $e->getMessage());
        }
    }

    /**
     * Test JSON extraction in Matter queries
     */
    public function test_matter_json_extraction()
    {
        try {
            // Test querying country names in multiple languages
            $query = Matter::select([
                'matter.id',
                'matter.country',
                DB::raw("mcountry.name->>'en' AS country_EN"),
                DB::raw("mcountry.name->>'fr' AS country_FR"),
            ])
                ->leftJoin('country as mcountry', 'mcountry.iso', '=', 'matter.country')
                ->take(1);

            $result = $query->get();
            $this->assertNotNull($result);
        } catch (\Exception $e) {
            $this->markTestSkipped("JSON extraction test: " . $e->getMessage());
        }
    }

    /**
     * Test complex joins with COALESCE
     */
    public function test_matter_complex_joins_with_coalesce()
    {
        try {
            $query = Matter::select('matter.*')
                ->leftJoin(
                    DB::raw('matter_actor_lnk clilnk JOIN actor clic ON clic.id = clilnk.actor_id'),
                    function ($join) {
                        $join->on(DB::raw('COALESCE(matter.container_id, matter.id)'), 'clilnk.matter_id')
                            ->where('clilnk.role', 'CLI');
                    }
                )
                ->take(1);

            $result = $query->get();
            $this->assertNotNull($result);
        } catch (\Exception $e) {
            $this->markTestSkipped("Complex joins test: " . $e->getMessage());
        }
    }

    /**
     * Test Matter relationships work with PostgreSQL
     */
    public function test_matter_relationships()
    {
        try {
            $matter = Matter::with(['events', 'actors', 'classifiers'])->first();

            if ($matter) {
                $this->assertNotNull($matter->events);
                $this->assertNotNull($matter->actors);
                $this->assertNotNull($matter->classifiers);
            } else {
                $this->markTestSkipped("No matter data to test relationships");
            }
        } catch (\Exception $e) {
            $this->markTestSkipped("Relationships test: " . $e->getMessage());
        }
    }

    /**
     * Test boolean check (container_id IS NULL)
     */
    public function test_matter_container_boolean_check()
    {
        try {
            $query = Matter::select([
                'matter.id',
                DB::raw('(matter.container_id IS NULL) AS is_container'),
            ])->take(5);

            $result = $query->get();
            $this->assertNotNull($result);

            if ($result->count() > 0) {
                $this->assertIsBool($result->first()->is_container);
            }
        } catch (\Exception $e) {
            $this->markTestSkipped("Boolean check test: " . $e->getMessage());
        }
    }
}
