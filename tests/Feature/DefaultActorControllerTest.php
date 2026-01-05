<?php

namespace Tests\Feature;

use App\Models\Actor;
use App\Models\Category;
use App\Models\DefaultActor;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class DefaultActorControllerTest extends TestCase
{
    /** @test */
    public function default_actors_can_be_filtered_by_actor_name()
    {
        $admin = User::factory()->admin()->create();
        // AGT role, PAT category, and US country already exist from seeded data

        $actorAlpha = Actor::factory()->create(['name' => 'Alpha Actor']);
        $actorBeta = Actor::factory()->create(['name' => 'Beta Actor']);
        $client = Actor::factory()->create(['name' => 'Client One']);

        // Use existing seeded data
        $role = Role::find('AGT');
        $category = Category::find('PAT');

        DefaultActor::create([
            'actor_id' => $actorAlpha->id,
            'role' => $role->code,
            'for_category' => $category->code,
            'for_country' => 'US',
            'for_client' => $client->id,
            'shared' => false,
        ]);

        DefaultActor::create([
            'actor_id' => $actorBeta->id,
            'role' => $role->code,
            'for_category' => $category->code,
            'for_country' => 'US',
            'for_client' => $client->id,
            'shared' => false,
        ]);

        $response = $this->actingAs($admin)->get('/default_actor?Actor=Alpha');

        $response->assertStatus(200);
        $response->assertViewHas('default_actors', function ($defaultActors) use ($actorAlpha) {
            return $defaultActors->count() === 1
                && $defaultActors->first()->actor_id === $actorAlpha->id;
        });
    }
}
