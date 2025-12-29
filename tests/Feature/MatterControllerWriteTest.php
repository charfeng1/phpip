<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Matter;
use App\Models\User;
use Tests\TestCase;

/**
 * Feature tests for MatterController write operations (POST/PUT/DELETE).
 *
 * Tests the create, update, and delete functionality including:
 * - Authorization for different user roles
 * - Validation error handling
 * - Successful CRUD operations
 * - Edge cases and error conditions
 */
class MatterControllerWriteTest extends TestCase
{
    protected User $adminUser;

    protected User $readWriteUser;

    protected User $readOnlyUser;

    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->adminUser = User::factory()->admin()->create();
        $this->readWriteUser = User::factory()->readWrite()->create();
        $this->readOnlyUser = User::factory()->readOnly()->create();
        $this->clientUser = User::factory()->client()->create();
    }

    // ==========================================
    // CREATE (POST) Tests
    // ==========================================

    /** @test */
    public function guests_cannot_create_matters()
    {
        $response = $this->postJson('/matter', [
            'category_code' => 'PAT',
            'caseref' => 'TEST001',
            'country' => 'US',
            'responsible' => 'admin',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function read_only_user_cannot_create_matters()
    {
        $response = $this->actingAs($this->readOnlyUser)
            ->postJson('/matter', [
                'category_code' => 'PAT',
                'caseref' => 'TEST001',
                'country' => 'US',
                'responsible' => $this->readOnlyUser->login,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function client_user_cannot_create_matters()
    {
        $response = $this->actingAs($this->clientUser)
            ->postJson('/matter', [
                'category_code' => 'PAT',
                'caseref' => 'TEST001',
                'country' => 'US',
                'responsible' => $this->clientUser->login,
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_create_matter()
    {
        $caseref = 'ADMTEST'.rand(1000, 9999);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/matter', [
                'category_code' => 'PAT',
                'caseref' => $caseref,
                'country' => 'US',
                'responsible' => $this->adminUser->login,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['redirect']);

        $this->assertDatabaseHas('matter', [
            'caseref' => $caseref,
            'country' => 'US',
            'category_code' => 'PAT',
        ]);
    }

    /** @test */
    public function read_write_user_can_create_matter()
    {
        $caseref = 'RWTEST'.rand(1000, 9999);

        $response = $this->actingAs($this->readWriteUser)
            ->postJson('/matter', [
                'category_code' => 'PAT',
                'caseref' => $caseref,
                'country' => 'US',
                'responsible' => $this->readWriteUser->login,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['redirect']);

        $this->assertDatabaseHas('matter', [
            'caseref' => $caseref,
            'country' => 'US',
        ]);
    }

    /** @test */
    public function create_matter_returns_validation_errors_for_missing_fields()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/matter', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_code', 'caseref', 'country', 'responsible']);
    }

    /** @test */
    public function create_matter_validates_category_code_exists()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/matter', [
                'category_code' => 'INVALID',
                'caseref' => 'TEST001',
                'country' => 'US',
                'responsible' => $this->adminUser->login,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_code']);
    }

    /** @test */
    public function create_matter_validates_country_exists()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/matter', [
                'category_code' => 'PAT',
                'caseref' => 'TEST001',
                'country' => 'XX',
                'responsible' => $this->adminUser->login,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['country']);
    }

    /** @test */
    public function create_matter_validates_caseref_max_length()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/matter', [
                'category_code' => 'PAT',
                'caseref' => str_repeat('A', 31),
                'country' => 'US',
                'responsible' => $this->adminUser->login,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['caseref']);
    }

    /** @test */
    public function create_matter_with_all_optional_fields()
    {
        $caseref = 'FULL'.rand(1000, 9999);

        $response = $this->actingAs($this->adminUser)
            ->postJson('/matter', [
                'category_code' => 'PAT',
                'caseref' => $caseref,
                'country' => 'US',
                'responsible' => $this->adminUser->login,
                'origin' => 'EP',
                'expire_date' => '2040-12-31',
                'dead' => false,
                'notes' => 'Test notes for complete matter',
                'operation' => 'new',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('matter', [
            'caseref' => $caseref,
            'origin' => 'EP',
            'dead' => false,
            'notes' => 'Test notes for complete matter',
        ]);
    }

    /** @test */
    public function create_matter_increments_idx_for_duplicate_uid()
    {
        $caseref = 'DUPTEST'.rand(1000, 9999);

        // Create first matter
        $this->actingAs($this->adminUser)
            ->postJson('/matter', [
                'category_code' => 'PAT',
                'caseref' => $caseref,
                'country' => 'US',
                'responsible' => $this->adminUser->login,
            ]);

        // Create second matter with same identifying fields
        $response = $this->actingAs($this->adminUser)
            ->postJson('/matter', [
                'category_code' => 'PAT',
                'caseref' => $caseref,
                'country' => 'US',
                'responsible' => $this->adminUser->login,
            ]);

        $response->assertStatus(200);

        // Second matter should have idx = 2
        $this->assertDatabaseHas('matter', [
            'caseref' => $caseref,
            'country' => 'US',
            'idx' => 2,
        ]);
    }

    /** @test */
    public function create_matter_validates_operation_value()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/matter', [
                'category_code' => 'PAT',
                'caseref' => 'TEST001',
                'country' => 'US',
                'responsible' => $this->adminUser->login,
                'operation' => 'invalid_operation',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['operation']);
    }

    // ==========================================
    // UPDATE (PUT/PATCH) Tests
    // ==========================================

    /** @test */
    public function guests_cannot_update_matters()
    {
        $matter = Matter::factory()->create();

        $response = $this->putJson("/matter/{$matter->id}", [
            'notes' => 'Updated notes',
        ]);

        $response->assertStatus(401);
    }

    /** @test */
    public function read_only_user_cannot_update_matters()
    {
        $matter = Matter::factory()->create();

        $response = $this->actingAs($this->readOnlyUser)
            ->putJson("/matter/{$matter->id}", [
                'notes' => 'Updated notes',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function client_user_cannot_update_matters()
    {
        $matter = Matter::factory()->create();

        $response = $this->actingAs($this->clientUser)
            ->putJson("/matter/{$matter->id}", [
                'notes' => 'Updated notes',
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_matter()
    {
        $matter = Matter::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->putJson("/matter/{$matter->id}", [
                'notes' => 'Admin updated notes',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('matter', [
            'id' => $matter->id,
            'notes' => 'Admin updated notes',
        ]);
    }

    /** @test */
    public function read_write_user_can_update_matter()
    {
        $matter = Matter::factory()->create();

        $response = $this->actingAs($this->readWriteUser)
            ->putJson("/matter/{$matter->id}", [
                'notes' => 'RW user updated notes',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('matter', [
            'id' => $matter->id,
            'notes' => 'RW user updated notes',
        ]);
    }

    /** @test */
    public function update_matter_can_change_dead_status()
    {
        $matter = Matter::factory()->create(['dead' => false]);

        $response = $this->actingAs($this->adminUser)
            ->putJson("/matter/{$matter->id}", [
                'dead' => true,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('matter', [
            'id' => $matter->id,
            'dead' => true,
        ]);
    }

    /** @test */
    public function update_matter_can_set_expire_date()
    {
        $matter = Matter::factory()->create(['expire_date' => null]);

        $response = $this->actingAs($this->adminUser)
            ->putJson("/matter/{$matter->id}", [
                'expire_date' => '2040-06-15',
            ]);

        $response->assertStatus(200);

        $matter->refresh();
        $this->assertEquals('2040-06-15', $matter->expire_date->format('Y-m-d'));
    }

    /** @test */
    public function update_matter_validates_country_exists()
    {
        $matter = Matter::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->putJson("/matter/{$matter->id}", [
                'country' => 'XX',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['country']);
    }

    /** @test */
    public function update_matter_validates_category_code_exists()
    {
        $matter = Matter::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->putJson("/matter/{$matter->id}", [
                'category_code' => 'INVALID',
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_code']);
    }

    /** @test */
    public function update_nonexistent_matter_returns_404()
    {
        $response = $this->actingAs($this->adminUser)
            ->putJson('/matter/999999', [
                'notes' => 'Should not work',
            ]);

        $response->assertStatus(404);
    }

    /** @test */
    public function update_matter_with_multiple_fields()
    {
        $matter = Matter::factory()->create([
            'dead' => false,
            'notes' => 'Original notes',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->putJson("/matter/{$matter->id}", [
                'dead' => true,
                'notes' => 'Updated to dead',
                'term_adjust' => 30,
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('matter', [
            'id' => $matter->id,
            'dead' => true,
            'notes' => 'Updated to dead',
            'term_adjust' => 30,
        ]);
    }

    /** @test */
    public function update_matter_tracks_updater()
    {
        $matter = Matter::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->putJson("/matter/{$matter->id}", [
                'notes' => 'Testing updater tracking',
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('matter', [
            'id' => $matter->id,
            'updater' => $this->adminUser->login,
        ]);
    }

    // ==========================================
    // DELETE Tests
    // ==========================================

    /** @test */
    public function guests_cannot_delete_matters()
    {
        $matter = Matter::factory()->create();

        $response = $this->deleteJson("/matter/{$matter->id}");

        $response->assertStatus(401);
    }

    /** @test */
    public function read_only_user_cannot_delete_matters()
    {
        $matter = Matter::factory()->create();

        $response = $this->actingAs($this->readOnlyUser)
            ->deleteJson("/matter/{$matter->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function client_user_cannot_delete_matters()
    {
        $matter = Matter::factory()->create();

        $response = $this->actingAs($this->clientUser)
            ->deleteJson("/matter/{$matter->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_matter()
    {
        $matter = Matter::factory()->create();
        $matterId = $matter->id;

        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/matter/{$matter->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('matter', [
            'id' => $matterId,
        ]);
    }

    /** @test */
    public function read_write_user_can_delete_matter()
    {
        $matter = Matter::factory()->create();
        $matterId = $matter->id;

        $response = $this->actingAs($this->readWriteUser)
            ->deleteJson("/matter/{$matter->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('matter', [
            'id' => $matterId,
        ]);
    }

    /** @test */
    public function delete_nonexistent_matter_returns_404()
    {
        $response = $this->actingAs($this->adminUser)
            ->deleteJson('/matter/999999');

        $response->assertStatus(404);
    }

    /** @test */
    public function delete_matter_also_deletes_related_events()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $eventId = $event->id;

        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/matter/{$matter->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('event', [
            'id' => $eventId,
        ]);
    }

    // ==========================================
    // Edit Form Access Tests
    // ==========================================

    /** @test */
    public function guests_cannot_access_edit_form()
    {
        $matter = Matter::factory()->create();
        Event::factory()->filing()->forMatter($matter)->create();

        $response = $this->get("/matter/{$matter->id}/edit");

        $response->assertRedirect('/login');
    }

    /** @test */
    public function read_only_user_cannot_access_edit_form()
    {
        $matter = Matter::factory()->create();
        Event::factory()->filing()->forMatter($matter)->create();

        $response = $this->actingAs($this->readOnlyUser)
            ->get("/matter/{$matter->id}/edit");

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_edit_form()
    {
        $matter = Matter::factory()->create();
        Event::factory()->filing()->forMatter($matter)->create();

        $response = $this->actingAs($this->adminUser)
            ->get("/matter/{$matter->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('matter.edit');
    }

    /** @test */
    public function read_write_user_can_access_edit_form()
    {
        $matter = Matter::factory()->create();
        Event::factory()->filing()->forMatter($matter)->create();

        $response = $this->actingAs($this->readWriteUser)
            ->get("/matter/{$matter->id}/edit");

        $response->assertStatus(200);
        $response->assertViewIs('matter.edit');
    }

    // ==========================================
    // Create Form Access Tests
    // ==========================================

    /** @test */
    public function guests_cannot_access_create_form()
    {
        $response = $this->get('/matter/create');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function read_only_user_cannot_access_create_form()
    {
        $response = $this->actingAs($this->readOnlyUser)
            ->get('/matter/create');

        $response->assertStatus(403);
    }
}
