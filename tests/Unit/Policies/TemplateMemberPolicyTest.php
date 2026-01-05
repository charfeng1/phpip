<?php

namespace Tests\Unit\Policies;

use App\Models\TemplateMember;
use App\Models\User;
use App\Policies\TemplateMemberPolicy;
use Tests\TestCase;

class TemplateMemberPolicyTest extends TestCase
{
    protected TemplateMemberPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TemplateMemberPolicy;
    }

    /** @test */
    public function admin_can_view_any_template_members()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function read_write_user_can_view_any_template_members()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_user_can_view_any_template_members()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_cannot_view_any_template_members()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($client));
    }

    /** @test */
    public function admin_can_view_template_member()
    {
        $admin = User::factory()->admin()->create();
        $templateMember = new TemplateMember;

        $this->assertTrue($this->policy->view($admin, $templateMember));
    }

    /** @test */
    public function read_only_user_can_view_template_member()
    {
        $user = User::factory()->readOnly()->create();
        $templateMember = new TemplateMember;

        $this->assertTrue($this->policy->view($user, $templateMember));
    }

    /** @test */
    public function client_cannot_view_template_member()
    {
        $client = User::factory()->client()->create();
        $templateMember = new TemplateMember;

        $this->assertFalse($this->policy->view($client, $templateMember));
    }

    /** @test */
    public function only_admin_can_create_template_member()
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
    public function only_admin_can_update_template_member()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $templateMember = new TemplateMember;

        $this->assertTrue($this->policy->update($admin, $templateMember));
        $this->assertFalse($this->policy->update($readWrite, $templateMember));
    }

    /** @test */
    public function read_only_user_cannot_update_template_member()
    {
        $user = User::factory()->readOnly()->create();
        $templateMember = new TemplateMember;

        $this->assertFalse($this->policy->update($user, $templateMember));
    }

    /** @test */
    public function client_cannot_update_template_member()
    {
        $client = User::factory()->client()->create();
        $templateMember = new TemplateMember;

        $this->assertFalse($this->policy->update($client, $templateMember));
    }

    /** @test */
    public function only_admin_can_delete_template_member()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $templateMember = new TemplateMember;

        $this->assertTrue($this->policy->delete($admin, $templateMember));
        $this->assertFalse($this->policy->delete($readWrite, $templateMember));
    }

    /** @test */
    public function read_only_user_cannot_delete_template_member()
    {
        $user = User::factory()->readOnly()->create();
        $templateMember = new TemplateMember;

        $this->assertFalse($this->policy->delete($user, $templateMember));
    }

    /** @test */
    public function client_cannot_delete_template_member()
    {
        $client = User::factory()->client()->create();
        $templateMember = new TemplateMember;

        $this->assertFalse($this->policy->delete($client, $templateMember));
    }
}
