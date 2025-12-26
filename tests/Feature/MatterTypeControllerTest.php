<?php

namespace Tests\Feature;

use App\Models\MatterType;
use App\Models\User;
use Tests\TestCase;

class MatterTypeControllerTest extends TestCase
{
    /** @test */
    public function guest_cannot_access_matter_types()
    {
        $response = $this->get(route('type.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_can_access_matter_type_index()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('type.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function read_only_user_can_access_matter_type_index()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->get(route('type.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_access_matter_type_index()
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->get(route('type.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_create_matter_type()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post(route('type.store'), [
            'code' => 'NEW',
            'type' => 'New Matter Type',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('matter_type', ['code' => 'NEW']);
    }

    /** @test */
    public function read_write_user_cannot_create_matter_type()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->post(route('type.store'), [
            'code' => 'RW',
            'type' => 'RW Type',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_matter_type()
    {
        $user = User::factory()->admin()->create();
        $matterType = MatterType::create([
            'code' => 'UPD',
            'type' => ['en' => 'To Update'],
        ]);

        $response = $this->actingAs($user)->put(route('type.update', $matterType), [
            'type' => 'Updated Type',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function read_write_user_cannot_update_matter_type()
    {
        $user = User::factory()->readWrite()->create();
        $matterType = MatterType::first() ?? MatterType::create([
            'code' => 'PRV',
            'type' => ['en' => 'Provisional'],
        ]);

        $response = $this->actingAs($user)->put(route('type.update', $matterType), [
            'type' => 'Changed Type',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_matter_type()
    {
        $user = User::factory()->admin()->create();
        $matterType = MatterType::create([
            'code' => 'DEL',
            'type' => ['en' => 'To Delete'],
        ]);

        $response = $this->actingAs($user)->delete(route('type.destroy', $matterType));

        $response->assertRedirect();
    }

    /** @test */
    public function matter_type_index_returns_view()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('type.index'));

        $response->assertViewIs('type.index');
    }
}
