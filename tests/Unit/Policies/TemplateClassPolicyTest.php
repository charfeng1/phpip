<?php

namespace Tests\Unit\Policies;

use App\Models\TemplateClass;
use App\Models\User;
use App\Policies\TemplateClassPolicy;
use Tests\TestCase;

class TemplateClassPolicyTest extends TestCase
{
    protected TemplateClassPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new TemplateClassPolicy;
    }

    /** @test */
    public function admin_can_view_any_template_classes()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function read_write_user_can_view_any_template_classes()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_user_can_view_any_template_classes()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_cannot_view_any_template_classes()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($client));
    }

    /** @test */
    public function admin_can_view_template_class()
    {
        $admin = User::factory()->admin()->create();
        $templateClass = new TemplateClass;

        $this->assertTrue($this->policy->view($admin, $templateClass));
    }

    /** @test */
    public function read_only_user_can_view_template_class()
    {
        $user = User::factory()->readOnly()->create();
        $templateClass = new TemplateClass;

        $this->assertTrue($this->policy->view($user, $templateClass));
    }

    /** @test */
    public function client_cannot_view_template_class()
    {
        $client = User::factory()->client()->create();
        $templateClass = new TemplateClass;

        $this->assertFalse($this->policy->view($client, $templateClass));
    }

    /** @test */
    public function only_admin_can_create_template_class()
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
    public function only_admin_can_update_template_class()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $templateClass = new TemplateClass;

        $this->assertTrue($this->policy->update($admin, $templateClass));
        $this->assertFalse($this->policy->update($readWrite, $templateClass));
    }

    /** @test */
    public function read_only_user_cannot_update_template_class()
    {
        $user = User::factory()->readOnly()->create();
        $templateClass = new TemplateClass;

        $this->assertFalse($this->policy->update($user, $templateClass));
    }

    /** @test */
    public function client_cannot_update_template_class()
    {
        $client = User::factory()->client()->create();
        $templateClass = new TemplateClass;

        $this->assertFalse($this->policy->update($client, $templateClass));
    }

    /** @test */
    public function only_admin_can_delete_template_class()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $templateClass = new TemplateClass;

        $this->assertTrue($this->policy->delete($admin, $templateClass));
        $this->assertFalse($this->policy->delete($readWrite, $templateClass));
    }

    /** @test */
    public function read_only_user_cannot_delete_template_class()
    {
        $user = User::factory()->readOnly()->create();
        $templateClass = new TemplateClass;

        $this->assertFalse($this->policy->delete($user, $templateClass));
    }

    /** @test */
    public function client_cannot_delete_template_class()
    {
        $client = User::factory()->client()->create();
        $templateClass = new TemplateClass;

        $this->assertFalse($this->policy->delete($client, $templateClass));
    }
}
