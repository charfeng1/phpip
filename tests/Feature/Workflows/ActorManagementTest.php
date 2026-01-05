<?php

namespace Tests\Feature\Workflows;

use App\Models\Actor;
use App\Models\ActorPivot;
use App\Models\Category;
use App\Models\Country;
use App\Models\Matter;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

/**
 * Integration tests for Actor Management workflow.
 *
 * Tests actor creation, role assignment, and matter relationships.
 */
class ActorManagementTest extends TestCase
{
    /** @test */
    public function actor_can_be_created()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);

        $response = $this->actingAs($user)->post(route('actor.store'), [
            'name' => 'Test Actor Company',
            'country' => $country->iso,
            'phy_person' => 0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('actor', ['name' => 'Test Actor Company']);
    }

    /** @test */
    public function actor_can_be_assigned_to_matter_as_client()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);
        $clientRole = Role::where('code', 'CLI')->first() ?? Role::factory()->create(['code' => 'CLI']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $actor = Actor::factory()->create(['country' => $country->iso]);

        $response = $this->actingAs($user)->postJson(route('actor-pivot.store'), [
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $clientRole->code,
            'shared' => 0,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('matter_actor_lnk', [
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $clientRole->code,
        ]);
    }

    /** @test */
    public function actor_can_be_assigned_multiple_roles_on_same_matter()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $actor = Actor::factory()->create(['country' => $country->iso]);

        // Assign as client
        $clientRole = Role::where('code', 'CLI')->first() ?? Role::factory()->create(['code' => 'CLI']);
        $this->actingAs($user)->postJson(route('actor-pivot.store'), [
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $clientRole->code,
            'shared' => 0,
        ]);

        // Assign as applicant
        $applicantRole = Role::where('code', 'APP')->first() ?? Role::factory()->create(['code' => 'APP']);
        $response = $this->actingAs($user)->postJson(route('actor-pivot.store'), [
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $applicantRole->code,
            'shared' => 0,
        ]);

        $response->assertStatus(201);

        // Verify both roles exist
        $this->assertDatabaseHas('matter_actor_lnk', [
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $clientRole->code,
        ]);
        $this->assertDatabaseHas('matter_actor_lnk', [
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $applicantRole->code,
        ]);
    }

    /** @test */
    public function actor_role_can_be_updated()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);
        $role = Role::where('code', 'CLI')->first() ?? Role::factory()->create(['code' => 'CLI']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $actor = Actor::factory()->create(['country' => $country->iso]);

        ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $role->code,
            'shared' => 0,
            'display_order' => 1,
        ]);
        // Refetch to get the auto-generated ID
        $actorPivot = ActorPivot::where('matter_id', $matter->id)
            ->where('actor_id', $actor->id)
            ->where('role', $role->code)
            ->firstOrFail();

        $response = $this->actingAs($user)->putJson(route('actor-pivot.update', $actorPivot), [
            'shared' => 1,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function actor_can_be_removed_from_matter()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);
        $role = Role::where('code', 'CLI')->first() ?? Role::factory()->create(['code' => 'CLI']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $actor = Actor::factory()->create(['country' => $country->iso]);

        ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $role->code,
            'shared' => 0,
            'display_order' => 1,
        ]);
        // Refetch to get the auto-generated ID
        $actorPivot = ActorPivot::where('matter_id', $matter->id)
            ->where('actor_id', $actor->id)
            ->where('role', $role->code)
            ->firstOrFail();

        $response = $this->actingAs($user)->deleteJson(route('actor-pivot.destroy', $actorPivot));

        $response->assertStatus(200);
        $this->assertDatabaseMissing('matter_actor_lnk', [
            'id' => $actorPivot->id,
        ]);
    }

    /** @test */
    public function actor_dependencies_can_be_viewed()
    {
        $user = User::factory()->readWrite()->create();

        $actor = Actor::factory()->create(['country' => 'US']);

        $response = $this->actingAs($user)->get("/actor/{$actor->id}/usedin");

        $response->assertStatus(200);
        $response->assertViewIs('actor.usedin');
    }

    /** @test */
    public function actor_can_be_updated()
    {
        $user = User::factory()->readWrite()->create();

        $actor = Actor::factory()->create([
            'name' => 'Original Name',
            'country' => 'US',
        ]);

        $response = $this->actingAs($user)->putJson(route('actor.update', $actor), [
            'name' => 'Updated Name',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('actor', [
            'id' => $actor->id,
            'name' => 'Updated Name',
        ]);
    }

    /** @test */
    public function actor_can_have_parent_company()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);

        $parentCompany = Actor::factory()->create([
            'name' => 'Parent Company',
            'country' => $country->iso,
            'phy_person' => 0,
        ]);

        $response = $this->actingAs($user)->post(route('actor.store'), [
            'name' => 'Subsidiary',
            'country' => $country->iso,
            'company_id' => $parentCompany->id,
            'phy_person' => 0,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('actor', [
            'name' => 'Subsidiary',
            'company_id' => $parentCompany->id,
        ]);
    }

    /** @test */
    public function multiple_actors_can_share_same_role_on_matter()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);
        $inventorRole = Role::where('code', 'INV')->first() ?? Role::factory()->create(['code' => 'INV']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $actor1 = Actor::factory()->create(['name' => 'Inventor 1', 'country' => $country->iso]);
        $actor2 = Actor::factory()->create(['name' => 'Inventor 2', 'country' => $country->iso]);

        // Assign first inventor
        $this->actingAs($user)->postJson(route('actor-pivot.store'), [
            'matter_id' => $matter->id,
            'actor_id' => $actor1->id,
            'role' => $inventorRole->code,
            'shared' => 0,
        ]);

        // Assign second inventor
        $response = $this->actingAs($user)->postJson(route('actor-pivot.store'), [
            'matter_id' => $matter->id,
            'actor_id' => $actor2->id,
            'role' => $inventorRole->code,
            'shared' => 0,
        ]);

        $response->assertStatus(201);

        // Verify both actors have the same role
        $this->assertEquals(2, ActorPivot::where('matter_id', $matter->id)
            ->where('role', $inventorRole->code)
            ->count());
    }

    /** @test */
    public function actor_display_order_is_maintained_within_role()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);
        $inventorRole = Role::where('code', 'INV')->first() ?? Role::factory()->create(['code' => 'INV']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $actor1 = Actor::factory()->create(['name' => 'First Inventor', 'country' => $country->iso]);
        $actor2 = Actor::factory()->create(['name' => 'Second Inventor', 'country' => $country->iso]);
        $actor3 = Actor::factory()->create(['name' => 'Third Inventor', 'country' => $country->iso]);

        // Add three inventors
        $this->actingAs($user)->postJson(route('actor-pivot.store'), [
            'matter_id' => $matter->id,
            'actor_id' => $actor1->id,
            'role' => $inventorRole->code,
            'shared' => 0,
        ]);

        $this->actingAs($user)->postJson(route('actor-pivot.store'), [
            'matter_id' => $matter->id,
            'actor_id' => $actor2->id,
            'role' => $inventorRole->code,
            'shared' => 0,
        ]);

        $this->actingAs($user)->postJson(route('actor-pivot.store'), [
            'matter_id' => $matter->id,
            'actor_id' => $actor3->id,
            'role' => $inventorRole->code,
            'shared' => 0,
        ]);

        // Verify display_order is sequential
        $pivots = ActorPivot::where('matter_id', $matter->id)
            ->where('role', $inventorRole->code)
            ->orderBy('display_order')
            ->get();

        $this->assertEquals(1, $pivots[0]->display_order);
        $this->assertEquals(2, $pivots[1]->display_order);
        $this->assertEquals(3, $pivots[2]->display_order);
    }
}
