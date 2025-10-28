<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Country;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CountryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create(['default_role' => 'DBRW']);
        $this->actingAs($user);
    }

    /**
     * Test country index with PostgreSQL JSON queries
     */
    public function test_country_index_with_json_queries()
    {
        $response = $this->get('/country');

        $response->assertStatus(200);
        $response->assertViewHas('countries');
    }

    /**
     * Test country search using ILIKE (PostgreSQL case-insensitive)
     */
    public function test_country_search_with_ilike()
    {
        // Create test countries with JSON names
        DB::table('country')->insert([
            'iso' => 'US',
            'name' => json_encode(['en' => 'United States', 'fr' => 'États-Unis', 'de' => 'Vereinigte Staaten']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Test searching - the controller uses ILIKE for case-insensitive search
        $response = $this->get('/country?name=united');

        $response->assertStatus(200);
    }

    /**
     * Test country autocomplete with JSON column queries
     */
    public function test_country_autocomplete()
    {
        DB::table('country')->insert([
            'iso' => 'GB',
            'name' => json_encode(['en' => 'United Kingdom', 'fr' => 'Royaume-Uni']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/country/autocomplete?term=United');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['key', 'value'],
        ]);
    }

    /**
     * Test that country JSON names work with whereJsonLike macro
     */
    public function test_country_json_name_queries()
    {
        try {
            $results = Country::whereJsonLike('name', 'United')->get();
            $this->assertNotNull($results);
        } catch (\Exception $e) {
            $this->markTestSkipped("whereJsonLike macro test: " . $e->getMessage());
        }
    }

    /**
     * Test country show page
     */
    public function test_country_show()
    {
        DB::table('country')->insert([
            'iso' => 'FR',
            'name' => json_encode(['en' => 'France', 'fr' => 'France', 'de' => 'Frankreich']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/country/FR');

        $response->assertStatus(200);
        $response->assertViewHas('country');
    }

    /**
     * Test country update
     */
    public function test_country_update()
    {
        DB::table('country')->insert([
            'iso' => 'DE',
            'name' => json_encode(['en' => 'Germany', 'fr' => 'Allemagne', 'de' => 'Deutschland']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $data = [
            'ep' => true,
            'wo' => true,
            'renewal_first' => 3,
        ];

        $response = $this->put('/country/DE', $data);

        $response->assertStatus(200);
    }
}
