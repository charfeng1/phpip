<?php

namespace Tests\Unit\Policies;

use App\Models\RenewalsLog;
use App\Models\User;
use App\Policies\RenewalPolicy;
use Tests\TestCase;

class RenewalPolicyTest extends TestCase
{
    protected RenewalPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new RenewalPolicy();
    }

    /** @test */
    public function admin_can_view_any_renewals_logs()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function read_write_user_can_view_any_renewals_logs()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_user_can_view_any_renewals_logs()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_cannot_view_any_renewals_logs()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($client));
    }

    /** @test */
    public function admin_can_view_renewals_log()
    {
        $admin = User::factory()->admin()->create();
        $renewalsLog = new RenewalsLog();

        $this->assertTrue($this->policy->view($admin, $renewalsLog));
    }

    /** @test */
    public function read_only_user_can_view_renewals_log()
    {
        $user = User::factory()->readOnly()->create();
        $renewalsLog = new RenewalsLog();

        $this->assertTrue($this->policy->view($user, $renewalsLog));
    }

    /** @test */
    public function admin_can_create_renewals_log()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->create($admin));
    }

    /** @test */
    public function read_write_user_can_create_renewals_log()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function read_only_user_cannot_create_renewals_log()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertFalse($this->policy->create($user));
    }

    /** @test */
    public function client_cannot_create_renewals_log()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->create($client));
    }

    /** @test */
    public function admin_can_update_renewals_log()
    {
        $admin = User::factory()->admin()->create();
        $renewalsLog = new RenewalsLog();

        $this->assertTrue($this->policy->update($admin, $renewalsLog));
    }

    /** @test */
    public function read_write_user_can_update_renewals_log()
    {
        $user = User::factory()->readWrite()->create();
        $renewalsLog = new RenewalsLog();

        $this->assertTrue($this->policy->update($user, $renewalsLog));
    }

    /** @test */
    public function read_only_user_cannot_update_renewals_log()
    {
        $user = User::factory()->readOnly()->create();
        $renewalsLog = new RenewalsLog();

        $this->assertFalse($this->policy->update($user, $renewalsLog));
    }

    /** @test */
    public function client_cannot_update_renewals_log()
    {
        $client = User::factory()->client()->create();
        $renewalsLog = new RenewalsLog();

        $this->assertFalse($this->policy->update($client, $renewalsLog));
    }

    /** @test */
    public function admin_can_delete_renewals_log()
    {
        $admin = User::factory()->admin()->create();
        $renewalsLog = new RenewalsLog();

        $this->assertTrue($this->policy->delete($admin, $renewalsLog));
    }

    /** @test */
    public function read_write_user_can_delete_renewals_log()
    {
        $user = User::factory()->readWrite()->create();
        $renewalsLog = new RenewalsLog();

        $this->assertTrue($this->policy->delete($user, $renewalsLog));
    }

    /** @test */
    public function read_only_user_cannot_delete_renewals_log()
    {
        $user = User::factory()->readOnly()->create();
        $renewalsLog = new RenewalsLog();

        $this->assertFalse($this->policy->delete($user, $renewalsLog));
    }

    /** @test */
    public function client_cannot_delete_renewals_log()
    {
        $client = User::factory()->client()->create();
        $renewalsLog = new RenewalsLog();

        $this->assertFalse($this->policy->delete($client, $renewalsLog));
    }
}
