<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Actor;
use App\Models\Country;
use App\Models\EventName;
use App\Models\Matter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AutocompleteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    /**
     * Test matter autocomplete with whereLike (PostgreSQL compatible)
     */
    public function test_matter_autocomplete()
    {
        Matter::factory()->create(['uid' => 'TEST-001-US']);
        Matter::factory()->create(['uid' => 'TEST-002-EP']);

        $response = $this->get('/matter/autocomplete?term=TEST');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['key', 'value'],
        ]);
    }

    /**
     * Test actor autocomplete with COALESCE and whereLike
     */
    public function test_actor_autocomplete()
    {
        Actor::factory()->create(['name' => 'Test Company Inc.']);
        Actor::factory()->create(['display_name' => 'Test Corp', 'name' => 'Test Corporation']);

        $response = $this->get('/actor/autocomplete?term=Test');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['key', 'value'],
        ]);
    }

    /**
     * Test country autocomplete with JSON column queries
     */
    public function test_country_autocomplete_with_json()
    {
        DB::table('country')->insert([
            'iso' => 'US',
            'name' => json_encode(['en' => 'United States', 'fr' => 'États-Unis']),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/country/autocomplete?term=Unit');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['key', 'value'],
        ]);
    }

    /**
     * Test event name autocomplete with whereJsonLike
     */
    public function test_event_name_autocomplete()
    {
        DB::table('event_name')->insert([
            'code' => 'FIL',
            'name' => json_encode(['en' => 'Filing', 'fr' => 'Dépôt']),
            'is_task' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get('/event-name/autocomplete/0?term=Fil');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['key', 'value'],
        ]);
    }

    /**
     * Test user autocomplete with whereLike
     */
    public function test_user_autocomplete()
    {
        User::factory()->create(['name' => 'John Doe', 'login' => 'johnd']);
        User::factory()->create(['name' => 'Jane Smith', 'login' => 'janes']);

        $response = $this->get('/user/autocomplete?term=John');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['key', 'value'],
        ]);
    }

    /**
     * Test that all autocomplete endpoints use PostgreSQL-compatible syntax
     */
    public function test_all_autocomplete_endpoints_work()
    {
        $endpoints = [
            '/matter/autocomplete?term=test',
            '/caseref/new?term=TEST',
            '/event-name/autocomplete/0?term=fil',
            '/event-name/autocomplete/1?term=ren',
            '/user/autocomplete?term=admin',
            '/actor/autocomplete?term=test',
            '/role/autocomplete?term=cli',
            '/country/autocomplete?term=us',
            '/category/autocomplete?term=pat',
            '/type/autocomplete?term=div',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->get($endpoint);
            $response->assertStatus(200);
            $response->assertJson([]);  // Should return JSON array
        }
    }
}
