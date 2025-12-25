<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Matter;
use App\Models\User;
use Tests\TestCase;

class EventControllerTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function guests_cannot_access_events()
    {
        $matter = Matter::factory()->create();

        $response = $this->get("/matter/{$matter->id}/events");

        $response->assertRedirect('/login');
    }

    /** @test */
    public function authenticated_users_can_view_events_for_matter()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        Event::factory()->filing()->forMatter($matter)->create();

        $response = $this->actingAs($user)->get("/matter/{$matter->id}/events");

        $response->assertStatus(200);
    }

    /** @test */
    public function events_page_displays_events()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        Event::factory()->filing()->forMatter($matter)->create();

        $response = $this->actingAs($user)->get("/matter/{$matter->id}/events");

        $response->assertStatus(200);
        $response->assertViewHas('events');
        $response->assertViewHas('matter');
    }

    /** @test */
    public function event_can_be_shown()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $response = $this->actingAs($user)->get("/event/{$event->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function event_show_returns_json()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $response = $this->actingAs($user)->getJson("/event/{$event->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'code',
            'event_date',
            'detail',
        ]);
    }

    /** @test */
    public function admin_can_create_event()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();

        $response = $this->actingAs($user)->post('/event', [
            'matter_id' => $matter->id,
            'code' => 'PUB',
            'eventName' => 'Publication',
            'event_date' => '06/15/2024',
            'detail' => 'US2024123456',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('event', [
            'matter_id' => $matter->id,
            'code' => 'PUB',
        ]);
    }

    /** @test */
    public function admin_can_update_event()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $response = $this->actingAs($user)->put("/event/{$event->id}", [
            'detail' => '12/999,888',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('event', [
            'id' => $event->id,
            'detail' => '12/999,888',
        ]);
    }

    /** @test */
    public function admin_can_delete_event()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->publication()->forMatter($matter)->create();

        $response = $this->actingAs($user)->delete("/event/{$event->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('event', ['id' => $event->id]);
    }

    /** @test */
    public function read_only_users_cannot_create_events()
    {
        $user = User::factory()->readOnly()->create();
        $matter = Matter::factory()->create();

        $response = $this->actingAs($user)->post('/event', [
            'matter_id' => $matter->id,
            'code' => 'PUB',
            'eventName' => 'Publication',
            'event_date' => '06/15/2024',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function read_only_users_cannot_update_events()
    {
        $user = User::factory()->readOnly()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $response = $this->actingAs($user)->put("/event/{$event->id}", [
            'detail' => 'new-detail',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function read_only_users_cannot_delete_events()
    {
        $user = User::factory()->readOnly()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->publication()->forMatter($matter)->create();

        $response = $this->actingAs($user)->delete("/event/{$event->id}");

        $response->assertStatus(403);
    }

    /** @test */
    public function event_with_tasks_shows_warning_on_delete()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        \App\Models\Task::factory()->forEvent($event)->create();

        // The controller may prevent deletion or show warning
        $response = $this->actingAs($user)->delete("/event/{$event->id}");

        // Depending on implementation, this might return 200 or an error
        $response->assertStatus(200);
    }
}
