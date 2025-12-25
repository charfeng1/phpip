<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    /** @test */
    public function guest_cannot_access_roles()
    {
        $response = $this->get(route('role.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_can_access_role_index()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('role.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function read_only_user_can_access_role_index()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->get(route('role.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_access_role_index()
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->get(route('role.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_create_role()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post(route('role.store'), [
            'code' => 'NEW',
            'name' => 'New Role',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('actor_role', ['code' => 'NEW']);
    }

    /** @test */
    public function read_write_user_cannot_create_role()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->post(route('role.store'), [
            'code' => 'RW',
            'name' => 'RW Role',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_role()
    {
        $user = User::factory()->admin()->create();
        $role = Role::factory()->create(['code' => 'UPD']);

        $response = $this->actingAs($user)->put(route('role.update', $role), [
            'name' => 'Updated Role Name',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function read_write_user_cannot_update_role()
    {
        $user = User::factory()->readWrite()->create();
        $role = Role::find('CLI') ?? Role::factory()->create(['code' => 'CLI']);

        $response = $this->actingAs($user)->put(route('role.update', $role), [
            'name' => 'Changed Name',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_role()
    {
        $user = User::factory()->admin()->create();
        $role = Role::factory()->create(['code' => 'DEL']);

        $response = $this->actingAs($user)->delete(route('role.destroy', $role));

        $response->assertRedirect();
    }

    /** @test */
    public function role_index_returns_roles_view()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('role.index'));

        $response->assertViewIs('role.index');
    }
}
