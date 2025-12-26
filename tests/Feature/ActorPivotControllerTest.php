<?php

namespace Tests\Feature;

use App\Enums\ActorRole;
use App\Models\Actor;
use App\Models\ActorPivot;
use App\Models\Category;
use App\Models\Country;
use App\Models\Matter;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class ActorPivotControllerTest extends TestCase
{
    protected User $adminUser;

    protected User $readWriteUser;

    protected User $readOnlyUser;

    protected User $clientUser;

    protected Country $country;

    protected Category $category;

    protected Role $role;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users deterministically using factories
        $this->adminUser = User::factory()->admin()->create();
        $this->readWriteUser = User::factory()->readWrite()->create();
        $this->readOnlyUser = User::factory()->readOnly()->create();
        $this->clientUser = User::factory()->client()->create();

        // Create required reference data - use factory for new data, existing seed data for roles
        $this->country = Country::factory()->create();
        $this->category = Category::factory()->create();
        // Use existing role from seed data or create new one if not present
        $this->role = Role::firstOrCreate(
            ['code' => ActorRole::CLIENT->value],
            ['name' => json_encode(['en' => 'Client', 'fr' => 'Client']), 'shareable' => true]
        );
    }

    /**
     * Helper to create a matter with actor pivot for testing
     */
    protected function createActorPivot(array $attributes = []): ActorPivot
    {
        $matter = Matter::factory()->create([
            'category_code' => $this->category->code,
            'country' => $this->country->iso,
        ]);

        $actor = Actor::factory()->create(['country' => $this->country->iso]);

        $pivotData = array_merge([
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $this->role->code,
            'shared' => 0,
            'display_order' => 1,
        ], $attributes);

        ActorPivot::create($pivotData);

        // Fetch the created pivot to get the ID (since incrementing is false)
        return ActorPivot::where('matter_id', $pivotData['matter_id'])
            ->where('actor_id', $pivotData['actor_id'])
            ->where('role', $pivotData['role'])
            ->firstOrFail();
    }

    /** @test */
    public function guest_cannot_access_actor_pivot_routes()
    {
        $response = $this->postJson(route('actor-pivot.store'), []);

        $response->assertStatus(401);
    }

    /** @test */
    public function client_cannot_access_actor_pivot_routes()
    {
        $response = $this->actingAs($this->clientUser)->postJson(route('actor-pivot.store'), []);

        $response->assertStatus(403);
    }

    /** @test */
    public function read_write_user_can_store_actor_pivot()
    {
        $matter = Matter::factory()->create([
            'category_code' => $this->category->code,
            'country' => $this->country->iso,
        ]);

        $actor = Actor::factory()->create(['country' => $this->country->iso]);

        $response = $this->actingAs($this->readWriteUser)->postJson(route('actor-pivot.store'), [
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $this->role->code,
            'shared' => 0,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('matter_actor_lnk', [
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $this->role->code,
        ]);
    }

    /** @test */
    public function admin_can_store_actor_pivot()
    {
        $matter = Matter::factory()->create([
            'category_code' => $this->category->code,
            'country' => $this->country->iso,
        ]);

        $actor = Actor::factory()->create(['country' => $this->country->iso]);
        $applicantRole = Role::firstOrCreate(
            ['code' => ActorRole::APPLICANT->value],
            ['name' => json_encode(['en' => 'Applicant', 'fr' => 'Demandeur']), 'shareable' => true]
        );

        $response = $this->actingAs($this->adminUser)->postJson(route('actor-pivot.store'), [
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $applicantRole->code,
            'shared' => 0,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('matter_actor_lnk', [
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $applicantRole->code,
        ]);
    }

    /** @test */
    public function read_only_user_cannot_store_actor_pivot()
    {
        $response = $this->actingAs($this->readOnlyUser)->postJson(route('actor-pivot.store'), [
            'matter_id' => 1,
            'actor_id' => 1,
            'role' => ActorRole::CLIENT->value,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function read_write_user_can_update_actor_pivot()
    {
        $actorPivot = $this->createActorPivot(['shared' => 0]);

        $response = $this->actingAs($this->readWriteUser)->putJson(route('actor-pivot.update', $actorPivot), [
            'shared' => 1,
        ]);

        $response->assertStatus(200);

        // Verify database was updated
        $this->assertDatabaseHas('matter_actor_lnk', [
            'id' => $actorPivot->id,
            'shared' => 1,
        ]);
    }

    /** @test */
    public function read_write_user_can_delete_actor_pivot()
    {
        $actorPivot = $this->createActorPivot();
        $actorPivotId = $actorPivot->id;

        $response = $this->actingAs($this->readWriteUser)->deleteJson(route('actor-pivot.destroy', $actorPivot));

        $response->assertStatus(200);

        // Verify database record was deleted
        $this->assertDatabaseMissing('matter_actor_lnk', [
            'id' => $actorPivotId,
        ]);
    }

    /** @test */
    public function read_only_user_cannot_delete_actor_pivot()
    {
        $actorPivot = $this->createActorPivot();

        $response = $this->actingAs($this->readOnlyUser)->deleteJson(route('actor-pivot.destroy', $actorPivot));

        $response->assertStatus(403);

        // Verify record still exists
        $this->assertDatabaseHas('matter_actor_lnk', [
            'id' => $actorPivot->id,
        ]);
    }

    /** @test */
    public function admin_can_view_actor_used_in()
    {
        $actor = Actor::factory()->create(['country' => $this->country->iso]);

        $response = $this->actingAs($this->adminUser)->get("/actor/{$actor->id}/usedin");

        $response->assertStatus(200);
        $response->assertViewIs('actor.usedin');
    }

    /** @test */
    public function read_write_user_can_view_actor_used_in()
    {
        $actor = Actor::factory()->create(['country' => $this->country->iso]);

        $response = $this->actingAs($this->readWriteUser)->get("/actor/{$actor->id}/usedin");

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_view_actor_used_in()
    {
        $actor = Actor::factory()->create(['country' => $this->country->iso]);

        $response = $this->actingAs($this->clientUser)->get("/actor/{$actor->id}/usedin");

        $response->assertStatus(403);
    }
}
