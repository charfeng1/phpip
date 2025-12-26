<?php

namespace Tests\Feature;

use App\Models\EventName;
use App\Models\User;
use Tests\TestCase;

class EventNameControllerTest extends TestCase
{
    /** @test */
    public function guest_cannot_access_event_names()
    {
        $response = $this->get(route('eventname.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_can_access_event_name_index()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('eventname.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function read_only_user_can_access_event_name_index()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->get(route('eventname.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_access_event_name_index()
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->get(route('eventname.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_create_event_name()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post(route('eventname.store'), [
            'code' => 'NEW',
            'name' => 'New Event Name',
            'is_task' => false,
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('event_name', ['code' => 'NEW']);
    }

    /** @test */
    public function read_write_user_cannot_create_event_name()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->post(route('eventname.store'), [
            'code' => 'RW',
            'name' => 'RW Event',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_event_name()
    {
        $user = User::factory()->admin()->create();
        $eventName = EventName::factory()->create();

        $response = $this->actingAs($user)->put(route('eventname.update', $eventName), [
            'name' => 'Updated Event Name',
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function read_write_user_cannot_update_event_name()
    {
        $user = User::factory()->readWrite()->create();
        $eventName = EventName::first() ?? EventName::factory()->create();

        $response = $this->actingAs($user)->put(route('eventname.update', $eventName), [
            'name' => 'Changed Name',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_event_name()
    {
        $user = User::factory()->admin()->create();
        $eventName = EventName::factory()->create();

        $response = $this->actingAs($user)->delete(route('eventname.destroy', $eventName));

        $response->assertStatus(200);
    }

    /** @test */
    public function event_name_index_returns_view()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('eventname.index'));

        $response->assertViewIs('eventname.index');
    }
}
