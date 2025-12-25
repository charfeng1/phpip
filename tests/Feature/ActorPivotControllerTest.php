<?php

namespace Tests\Feature;

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
    /** @test */
    public function guest_cannot_access_actor_pivot_routes()
    {
        $response = $this->postJson(route('actor-pivot.store'), []);

        $response->assertStatus(401);
    }

    /** @test */
    public function client_cannot_access_actor_pivot_routes()
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->postJson(route('actor-pivot.store'), []);

        $response->assertStatus(403);
    }

    /** @test */
    public function read_write_user_can_store_actor_pivot()
    {
        $user = User::factory()->readWrite()->create();

        // Ensure required related data exists
        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);
        $role = Role::first() ?? Role::factory()->create(['code' => 'CLI']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $actor = Actor::factory()->create(['country' => $country->iso]);

        $response = $this->actingAs($user)->postJson(route('actor-pivot.store'), [
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $role->code,
            'shared' => 0,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('matter_actor_lnk', [
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $role->code,
        ]);
    }

    /** @test */
    public function admin_can_store_actor_pivot()
    {
        $user = User::factory()->admin()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);
        $role = Role::first() ?? Role::factory()->create(['code' => 'APP']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $actor = Actor::factory()->create(['country' => $country->iso]);

        $response = $this->actingAs($user)->postJson(route('actor-pivot.store'), [
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $role->code,
            'shared' => 0,
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function read_only_user_cannot_store_actor_pivot()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->postJson(route('actor-pivot.store'), [
            'matter_id' => 1,
            'actor_id' => 1,
            'role' => 'CLI',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function read_write_user_can_update_actor_pivot()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);
        $role = Role::first() ?? Role::factory()->create(['code' => 'CLI']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $actor = Actor::factory()->create(['country' => $country->iso]);

        $actorPivot = ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $role->code,
            'shared' => 0,
            'display_order' => 1,
        ]);

        $response = $this->actingAs($user)->putJson(route('actor-pivot.update', $actorPivot), [
            'shared' => 1,
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function read_write_user_can_delete_actor_pivot()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);
        $role = Role::first() ?? Role::factory()->create(['code' => 'CLI']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $actor = Actor::factory()->create(['country' => $country->iso]);

        $actorPivot = ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $role->code,
            'shared' => 0,
            'display_order' => 1,
        ]);

        $response = $this->actingAs($user)->deleteJson(route('actor-pivot.destroy', $actorPivot));

        $response->assertStatus(200);
    }

    /** @test */
    public function read_only_user_cannot_delete_actor_pivot()
    {
        $user = User::factory()->readOnly()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);
        $role = Role::first() ?? Role::factory()->create(['code' => 'CLI']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $actor = Actor::factory()->create(['country' => $country->iso]);

        $actorPivot = ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $role->code,
            'shared' => 0,
            'display_order' => 1,
        ]);

        $response = $this->actingAs($user)->deleteJson(route('actor-pivot.destroy', $actorPivot));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_actor_used_in()
    {
        $user = User::factory()->admin()->create();
        $actor = Actor::factory()->create(['country' => 'US']);

        $response = $this->actingAs($user)->get("/actor/{$actor->id}/usedin");

        $response->assertStatus(200);
        $response->assertViewIs('actor.usedin');
    }

    /** @test */
    public function read_write_user_can_view_actor_used_in()
    {
        $user = User::factory()->readWrite()->create();
        $actor = Actor::factory()->create(['country' => 'US']);

        $response = $this->actingAs($user)->get("/actor/{$actor->id}/usedin");

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_view_actor_used_in()
    {
        $user = User::factory()->client()->create();
        $actor = Actor::factory()->create(['country' => 'US']);

        $response = $this->actingAs($user)->get("/actor/{$actor->id}/usedin");

        $response->assertStatus(403);
    }
}
