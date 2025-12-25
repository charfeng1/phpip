<?php

namespace Tests\Unit\Policies;

use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use Tests\TestCase;

class RolePolicyTest extends TestCase
{
    protected RolePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new RolePolicy();
    }

    /** @test */
    public function admin_can_view_any_roles()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function read_write_user_can_view_any_roles()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_user_can_view_any_roles()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_cannot_view_any_roles()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($client));
    }

    /** @test */
    public function admin_can_view_role()
    {
        $admin = User::factory()->admin()->create();
        $role = Role::find('CLI') ?? Role::factory()->create(['code' => 'CLI']);

        $this->assertTrue($this->policy->view($admin, $role));
    }

    /** @test */
    public function only_admin_can_create_role()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->create($admin));
        $this->assertFalse($this->policy->create($readWrite));
    }

    /** @test */
    public function only_admin_can_update_role()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $role = Role::find('CLI') ?? Role::factory()->create(['code' => 'CLI']);

        $this->assertTrue($this->policy->update($admin, $role));
        $this->assertFalse($this->policy->update($readWrite, $role));
    }

    /** @test */
    public function only_admin_can_delete_role()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $role = Role::find('CLI') ?? Role::factory()->create(['code' => 'CLI']);

        $this->assertTrue($this->policy->delete($admin, $role));
        $this->assertFalse($this->policy->delete($readWrite, $role));
    }
}
