<?php

namespace Tests\Unit\Policies;

use App\Models\ActorPivot;
use App\Models\User;
use App\Policies\ActorPivotPolicy;
use Tests\TestCase;

class ActorPivotPolicyTest extends TestCase
{
    protected ActorPivotPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ActorPivotPolicy;
    }

    /** @test */
    public function admin_can_view_any_actor_pivots()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function read_write_user_can_view_any_actor_pivots()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_user_can_view_any_actor_pivots()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_cannot_view_any_actor_pivots()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($client));
    }

    /** @test */
    public function admin_can_view_actor_pivot()
    {
        $admin = User::factory()->admin()->create();
        $actorPivot = new ActorPivot;

        $this->assertTrue($this->policy->view($admin, $actorPivot));
    }

    /** @test */
    public function read_only_user_can_view_actor_pivot()
    {
        $user = User::factory()->readOnly()->create();
        $actorPivot = new ActorPivot;

        $this->assertTrue($this->policy->view($user, $actorPivot));
    }

    /** @test */
    public function client_cannot_view_actor_pivot()
    {
        $client = User::factory()->client()->create();
        $actorPivot = new ActorPivot;

        $this->assertFalse($this->policy->view($client, $actorPivot));
    }

    /** @test */
    public function admin_can_create_actor_pivot()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->create($admin));
    }

    /** @test */
    public function read_write_user_can_create_actor_pivot()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function read_only_user_cannot_create_actor_pivot()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertFalse($this->policy->create($user));
    }

    /** @test */
    public function client_cannot_create_actor_pivot()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->create($client));
    }

    /** @test */
    public function admin_can_update_actor_pivot()
    {
        $admin = User::factory()->admin()->create();
        $actorPivot = new ActorPivot;

        $this->assertTrue($this->policy->update($admin, $actorPivot));
    }

    /** @test */
    public function read_write_user_can_update_actor_pivot()
    {
        $user = User::factory()->readWrite()->create();
        $actorPivot = new ActorPivot;

        $this->assertTrue($this->policy->update($user, $actorPivot));
    }

    /** @test */
    public function read_only_user_cannot_update_actor_pivot()
    {
        $user = User::factory()->readOnly()->create();
        $actorPivot = new ActorPivot;

        $this->assertFalse($this->policy->update($user, $actorPivot));
    }

    /** @test */
    public function client_cannot_update_actor_pivot()
    {
        $client = User::factory()->client()->create();
        $actorPivot = new ActorPivot;

        $this->assertFalse($this->policy->update($client, $actorPivot));
    }

    /** @test */
    public function admin_can_delete_actor_pivot()
    {
        $admin = User::factory()->admin()->create();
        $actorPivot = new ActorPivot;

        $this->assertTrue($this->policy->delete($admin, $actorPivot));
    }

    /** @test */
    public function read_write_user_can_delete_actor_pivot()
    {
        $user = User::factory()->readWrite()->create();
        $actorPivot = new ActorPivot;

        $this->assertTrue($this->policy->delete($user, $actorPivot));
    }

    /** @test */
    public function read_only_user_cannot_delete_actor_pivot()
    {
        $user = User::factory()->readOnly()->create();
        $actorPivot = new ActorPivot;

        $this->assertFalse($this->policy->delete($user, $actorPivot));
    }

    /** @test */
    public function client_cannot_delete_actor_pivot()
    {
        $client = User::factory()->client()->create();
        $actorPivot = new ActorPivot;

        $this->assertFalse($this->policy->delete($client, $actorPivot));
    }
}
