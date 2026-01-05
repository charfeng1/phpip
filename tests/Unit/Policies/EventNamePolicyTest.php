<?php

namespace Tests\Unit\Policies;

use App\Models\EventName;
use App\Models\User;
use App\Policies\EventNamePolicy;
use Tests\TestCase;

class EventNamePolicyTest extends TestCase
{
    protected EventNamePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new EventNamePolicy;
    }

    /** @test */
    public function admin_can_view_any_event_names()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function read_write_user_can_view_any_event_names()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_user_can_view_any_event_names()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_cannot_view_any_event_names()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($client));
    }

    /** @test */
    public function admin_can_view_event_name()
    {
        $admin = User::factory()->admin()->create();
        $eventName = EventName::find('FIL') ?? EventName::factory()->create(['code' => 'FIL']);

        $this->assertTrue($this->policy->view($admin, $eventName));
    }

    /** @test */
    public function only_admin_can_create_event_name()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->create($admin));
        $this->assertFalse($this->policy->create($readWrite));
    }

    /** @test */
    public function only_admin_can_update_event_name()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $eventName = EventName::find('FIL') ?? EventName::factory()->create(['code' => 'FIL']);

        $this->assertTrue($this->policy->update($admin, $eventName));
        $this->assertFalse($this->policy->update($readWrite, $eventName));
    }

    /** @test */
    public function only_admin_can_delete_event_name()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $eventName = EventName::find('FIL') ?? EventName::factory()->create(['code' => 'FIL']);

        $this->assertTrue($this->policy->delete($admin, $eventName));
        $this->assertFalse($this->policy->delete($readWrite, $eventName));
    }
}
