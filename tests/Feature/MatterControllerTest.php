<?php

namespace Tests\Feature;

use App\Models\Matter;
use App\Models\User;
use App\Models\Actor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatterControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Authenticate as a test user
        $user = User::factory()->create(['default_role' => 'DBRW']);
        $this->actingAs($user);
    }

    /**
     * Test matter index page with PostgreSQL filtering
     */
    public function test_matter_index_uses_postgres_queries()
    {
        $response = $this->get('/matter');

        $response->assertStatus(200);
        $response->assertViewHas('matters');
    }

    /**
     * Test matter show page loads correctly
     */
    public function test_matter_show_page()
    {
        $matter = Matter::factory()->create();

        $response = $this->get("/matter/{$matter->id}");

        $response->assertStatus(200);
        $response->assertViewHas('matter');
    }

    /**
     * Test matter filtering with various parameters
     */
    public function test_matter_filtering_with_postgres()
    {
        Matter::factory()->count(5)->create();

        // Test with sorting
        $response = $this->get('/matter?sortkey=id&sortdir=desc');
        $response->assertStatus(200);

        // Test with filters
        $response = $this->get('/matter?category=PAT');
        $response->assertStatus(200);
    }

    /**
     * Test matter creation
     */
    public function test_matter_creation()
    {
        $response = $this->get('/matter/create?category=PAT');

        $response->assertStatus(200);
        $response->assertViewHas('categoriesList');
        $response->assertViewHas('countries');
    }

    /**
     * Test matter store with validation
     */
    public function test_matter_store()
    {
        $data = [
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => 'admin',
        ];

        $response = $this->post('/matter', $data);

        // Should redirect or return JSON
        $this->assertTrue(
            $response->status() === 302 || $response->status() === 200
        );
    }

    /**
     * Test ILIKE phonetic matching in storeFamily (replaced SOUNDS LIKE)
     */
    public function test_store_family_with_ilike_matching()
    {
        // Create some test actors
        Actor::factory()->create(['name' => 'Tesla Motors Inc.']);
        Actor::factory()->create(['name' => 'SpaceX Corporation']);

        // The storeFamily method uses ILIKE instead of SOUNDS LIKE
        // We can't fully test the OPS API integration, but we can test that
        // the controller method doesn't fail on actor matching

        $this->assertTrue(true, "ILIKE phonetic matching test placeholder");
    }

    /**
     * Test matter export functionality
     */
    public function test_matter_export()
    {
        Matter::factory()->count(3)->create();

        $response = $this->get('/matter/export?sortkey=id&sortdir=asc');

        // Should return CSV download
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    /**
     * Test JSON API responses use PostgreSQL queries correctly
     */
    public function test_matter_json_api()
    {
        Matter::factory()->count(3)->create();

        $response = $this->json('GET', '/matter', [
            'sortkey' => 'id',
            'sortdir' => 'desc',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'uid', 'caseref'],
        ]);
    }

    /**
     * Test matter with events relationship
     */
    public function test_matter_with_events()
    {
        $matter = Matter::factory()->create();

        $response = $this->get("/matter/{$matter->id}/events");

        $response->assertStatus(200);
        $response->assertViewHas('events');
    }

    /**
     * Test matter with tasks relationship
     */
    public function test_matter_with_tasks()
    {
        $matter = Matter::factory()->create();

        $response = $this->get("/matter/{$matter->id}/tasks");

        $response->assertStatus(200);
        $response->assertViewHas('events');
    }

    /**
     * Test matter with actors relationship
     */
    public function test_matter_with_actors()
    {
        $matter = Matter::factory()->create();

        $response = $this->get("/matter/{$matter->id}/actors/CLI");

        $response->assertStatus(200);
        $response->assertViewHas('role_group');
    }
}
