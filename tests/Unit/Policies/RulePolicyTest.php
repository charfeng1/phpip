<?php

namespace Tests\Unit\Policies;

use App\Models\Rule;
use App\Models\User;
use App\Policies\RulePolicy;
use Tests\TestCase;

class RulePolicyTest extends TestCase
{
    protected RulePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new RulePolicy;
    }

    /** @test */
    public function admin_can_view_any_rules()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function read_write_user_can_view_any_rules()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_user_can_view_any_rules()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_cannot_view_any_rules()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($client));
    }

    /** @test */
    public function admin_can_view_rule()
    {
        $admin = User::factory()->admin()->create();
        $rule = new Rule;

        $this->assertTrue($this->policy->view($admin, $rule));
    }

    /** @test */
    public function read_only_user_can_view_rule()
    {
        $user = User::factory()->readOnly()->create();
        $rule = new Rule;

        $this->assertTrue($this->policy->view($user, $rule));
    }

    /** @test */
    public function client_cannot_view_rule()
    {
        $client = User::factory()->client()->create();
        $rule = new Rule;

        $this->assertFalse($this->policy->view($client, $rule));
    }

    /** @test */
    public function only_admin_can_create_rule()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $readOnly = User::factory()->readOnly()->create();
        $client = User::factory()->client()->create();

        $this->assertTrue($this->policy->create($admin));
        $this->assertFalse($this->policy->create($readWrite));
        $this->assertFalse($this->policy->create($readOnly));
        $this->assertFalse($this->policy->create($client));
    }

    /** @test */
    public function only_admin_can_update_rule()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $rule = new Rule;

        $this->assertTrue($this->policy->update($admin, $rule));
        $this->assertFalse($this->policy->update($readWrite, $rule));
    }

    /** @test */
    public function read_only_user_cannot_update_rule()
    {
        $user = User::factory()->readOnly()->create();
        $rule = new Rule;

        $this->assertFalse($this->policy->update($user, $rule));
    }

    /** @test */
    public function client_cannot_update_rule()
    {
        $client = User::factory()->client()->create();
        $rule = new Rule;

        $this->assertFalse($this->policy->update($client, $rule));
    }

    /** @test */
    public function only_admin_can_delete_rule()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $rule = new Rule;

        $this->assertTrue($this->policy->delete($admin, $rule));
        $this->assertFalse($this->policy->delete($readWrite, $rule));
    }

    /** @test */
    public function read_only_user_cannot_delete_rule()
    {
        $user = User::factory()->readOnly()->create();
        $rule = new Rule;

        $this->assertFalse($this->policy->delete($user, $rule));
    }

    /** @test */
    public function client_cannot_delete_rule()
    {
        $client = User::factory()->client()->create();
        $rule = new Rule;

        $this->assertFalse($this->policy->delete($client, $rule));
    }
}
