<?php

namespace Tests\Unit\Policies;

use App\Models\Actor;
use App\Models\User;
use App\Policies\ActorPolicy;
use Tests\TestCase;

class ActorPolicyTest extends TestCase
{
    protected ActorPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ActorPolicy;
    }

    // viewAny tests

    /** @test */
    public function admin_can_view_any_actors()
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_write_can_view_any_actors()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_can_view_any_actors()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_cannot_view_any_actors()
    {
        $user = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($user));
    }

    /** @test */
    public function user_with_no_role_cannot_view_any_actors()
    {
        $user = User::factory()->create(['default_role' => null]);

        $this->assertFalse($this->policy->viewAny($user));
    }

    // view tests

    /** @test */
    public function admin_can_view_actor()
    {
        $user = User::factory()->admin()->create();
        $actor = Actor::factory()->create();

        $this->assertTrue($this->policy->view($user, $actor));
    }

    /** @test */
    public function read_write_can_view_actor()
    {
        $user = User::factory()->readWrite()->create();
        $actor = Actor::factory()->create();

        $this->assertTrue($this->policy->view($user, $actor));
    }

    /** @test */
    public function read_only_can_view_actor()
    {
        $user = User::factory()->readOnly()->create();
        $actor = Actor::factory()->create();

        $this->assertTrue($this->policy->view($user, $actor));
    }

    /** @test */
    public function client_cannot_view_actor()
    {
        $user = User::factory()->client()->create();
        $actor = Actor::factory()->create();

        $this->assertFalse($this->policy->view($user, $actor));
    }

    // create tests

    /** @test */
    public function admin_can_create_actor()
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function read_write_can_create_actor()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function read_only_cannot_create_actor()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertFalse($this->policy->create($user));
    }

    /** @test */
    public function client_cannot_create_actor()
    {
        $user = User::factory()->client()->create();

        $this->assertFalse($this->policy->create($user));
    }

    // update tests

    /** @test */
    public function admin_can_update_actor()
    {
        $user = User::factory()->admin()->create();
        $actor = Actor::factory()->create();

        $this->assertTrue($this->policy->update($user, $actor));
    }

    /** @test */
    public function read_write_can_update_actor()
    {
        $user = User::factory()->readWrite()->create();
        $actor = Actor::factory()->create();

        $this->assertTrue($this->policy->update($user, $actor));
    }

    /** @test */
    public function read_only_cannot_update_actor()
    {
        $user = User::factory()->readOnly()->create();
        $actor = Actor::factory()->create();

        $this->assertFalse($this->policy->update($user, $actor));
    }

    /** @test */
    public function client_cannot_update_actor()
    {
        $user = User::factory()->client()->create();
        $actor = Actor::factory()->create();

        $this->assertFalse($this->policy->update($user, $actor));
    }

    // delete tests

    /** @test */
    public function admin_can_delete_actor()
    {
        $user = User::factory()->admin()->create();
        $actor = Actor::factory()->create();

        $this->assertTrue($this->policy->delete($user, $actor));
    }

    /** @test */
    public function read_write_can_delete_actor()
    {
        $user = User::factory()->readWrite()->create();
        $actor = Actor::factory()->create();

        $this->assertTrue($this->policy->delete($user, $actor));
    }

    /** @test */
    public function read_only_cannot_delete_actor()
    {
        $user = User::factory()->readOnly()->create();
        $actor = Actor::factory()->create();

        $this->assertFalse($this->policy->delete($user, $actor));
    }

    /** @test */
    public function client_cannot_delete_actor()
    {
        $user = User::factory()->client()->create();
        $actor = Actor::factory()->create();

        $this->assertFalse($this->policy->delete($user, $actor));
    }
}
