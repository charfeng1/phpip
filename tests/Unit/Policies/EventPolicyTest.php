<?php

namespace Tests\Unit\Policies;

use App\Models\Event;
use App\Models\Matter;
use App\Models\User;
use App\Policies\EventPolicy;
use Tests\TestCase;

class EventPolicyTest extends TestCase
{
    protected EventPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new EventPolicy;
    }

    /** @test */
    public function admin_can_view_any_events()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function read_write_user_can_view_any_events()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_user_can_view_any_events()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_can_view_any_events()
    {
        $client = User::factory()->client()->create();

        $this->assertTrue($this->policy->viewAny($client));
    }

    /** @test */
    public function admin_can_view_event()
    {
        $admin = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $this->assertTrue($this->policy->view($admin, $event));
    }

    /** @test */
    public function read_only_user_can_view_event()
    {
        $user = User::factory()->readOnly()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $this->assertTrue($this->policy->view($user, $event));
    }

    /** @test */
    public function admin_can_create_event()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->create($admin));
    }

    /** @test */
    public function read_write_user_can_create_event()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function read_only_user_cannot_create_event()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertFalse($this->policy->create($user));
    }

    /** @test */
    public function client_cannot_create_event()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->create($client));
    }

    /** @test */
    public function admin_can_update_event()
    {
        $admin = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $this->assertTrue($this->policy->update($admin, $event));
    }

    /** @test */
    public function read_write_user_can_update_event()
    {
        $user = User::factory()->readWrite()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $this->assertTrue($this->policy->update($user, $event));
    }

    /** @test */
    public function read_only_user_cannot_update_event()
    {
        $user = User::factory()->readOnly()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $this->assertFalse($this->policy->update($user, $event));
    }

    /** @test */
    public function admin_can_delete_event()
    {
        $admin = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $this->assertTrue($this->policy->delete($admin, $event));
    }

    /** @test */
    public function read_write_user_can_delete_event()
    {
        $user = User::factory()->readWrite()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $this->assertTrue($this->policy->delete($user, $event));
    }

    /** @test */
    public function read_only_user_cannot_delete_event()
    {
        $user = User::factory()->readOnly()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $this->assertFalse($this->policy->delete($user, $event));
    }

    /** @test */
    public function client_cannot_delete_event()
    {
        $client = User::factory()->client()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();

        $this->assertFalse($this->policy->delete($client, $event));
    }
}
