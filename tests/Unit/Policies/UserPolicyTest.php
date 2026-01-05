<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\UserPolicy;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    protected UserPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new UserPolicy;
    }

    // viewAny tests

    /** @test */
    public function admin_can_view_any_users()
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_write_cannot_view_any_users()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertFalse($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_cannot_view_any_users()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertFalse($this->policy->viewAny($user));
    }

    /** @test */
    public function client_cannot_view_any_users()
    {
        $user = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($user));
    }

    // view tests

    /** @test */
    public function admin_can_view_any_user()
    {
        $admin = User::factory()->admin()->create();
        $otherUser = User::factory()->create();

        $this->assertTrue($this->policy->view($admin, $otherUser));
    }

    /** @test */
    public function user_can_view_self()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->view($user, $user));
    }

    /** @test */
    public function non_admin_cannot_view_other_user()
    {
        $user = User::factory()->readWrite()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($this->policy->view($user, $otherUser));
    }

    /** @test */
    public function read_only_can_view_self()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->view($user, $user));
    }

    /** @test */
    public function client_can_view_self()
    {
        $user = User::factory()->client()->create();

        $this->assertTrue($this->policy->view($user, $user));
    }

    // create tests

    /** @test */
    public function admin_can_create_user()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->create($admin));
    }

    /** @test */
    public function read_write_cannot_create_user()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertFalse($this->policy->create($user));
    }

    /** @test */
    public function read_only_cannot_create_user()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertFalse($this->policy->create($user));
    }

    /** @test */
    public function client_cannot_create_user()
    {
        $user = User::factory()->client()->create();

        $this->assertFalse($this->policy->create($user));
    }

    // update tests

    /** @test */
    public function admin_can_update_any_user()
    {
        $admin = User::factory()->admin()->create();
        $otherUser = User::factory()->create();

        $this->assertTrue($this->policy->update($admin, $otherUser));
    }

    /** @test */
    public function user_can_update_self()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->update($user, $user));
    }

    /** @test */
    public function non_admin_cannot_update_other_user()
    {
        $user = User::factory()->readWrite()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($this->policy->update($user, $otherUser));
    }

    /** @test */
    public function read_only_can_update_self()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->update($user, $user));
    }

    /** @test */
    public function client_can_update_self()
    {
        $user = User::factory()->client()->create();

        $this->assertTrue($this->policy->update($user, $user));
    }

    // delete tests

    /** @test */
    public function admin_can_delete_any_user()
    {
        $admin = User::factory()->admin()->create();
        $otherUser = User::factory()->create();

        $this->assertTrue($this->policy->delete($admin, $otherUser));
    }

    /** @test */
    public function non_admin_cannot_delete_user()
    {
        $user = User::factory()->readWrite()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($this->policy->delete($user, $otherUser));
    }

    /** @test */
    public function non_admin_cannot_delete_self()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertFalse($this->policy->delete($user, $user));
    }

    /** @test */
    public function read_only_cannot_delete_any_user()
    {
        $user = User::factory()->readOnly()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($this->policy->delete($user, $otherUser));
    }

    /** @test */
    public function client_cannot_delete_any_user()
    {
        $user = User::factory()->client()->create();
        $otherUser = User::factory()->create();

        $this->assertFalse($this->policy->delete($user, $otherUser));
    }

    /** @test */
    public function admin_can_delete_self()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->delete($admin, $admin));
    }
}
