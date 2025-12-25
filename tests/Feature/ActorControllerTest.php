<?php

namespace Tests\Feature;

use App\Models\Actor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActorControllerTest extends TestCase
{
    /**
     * Test actor index and related pages.
     */
    public function test_index()
    {
        // Create test actor within transaction
        $actorId = DB::table('actor')->max('id') + 1;
        $actor = Actor::create([
            'id' => $actorId,
            'name' => 'Test Company Inc.',
            'first_name' => '',
            'display_name' => 'Test Company Inc.',
        ]);

        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        // Main page with actors list
        $response = $this->get('/actor');
        $response->assertStatus(200)
            ->assertViewHas('actorslist');

        // A detailed page
        $response = $this->get("/actor/{$actor->id}");
        $response->assertStatus(200)
            ->assertViewHas('actorInfo')
            ->assertSee('Main');  // Tab name in the actor show view

        // A page used-in
        $response = $this->get("/actor/{$actor->id}/usedin");
        $response->assertStatus(200)
            ->assertViewHas('matter_dependencies')
            ->assertViewHas('other_dependencies')
            ->assertSeeText('Matter Dependencies');

        // Autocompletion
        $response = $this->get('/actor/autocomplete?term=Test');
        $response->assertStatus(200)
            ->assertJsonFragment(['value' => 'Test Company Inc.']);
    }
}
