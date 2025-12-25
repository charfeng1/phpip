<?php

namespace Tests\Unit\Policies;

use App\Models\Category;
use App\Models\User;
use App\Policies\CategoryPolicy;
use Tests\TestCase;

class CategoryPolicyTest extends TestCase
{
    protected CategoryPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CategoryPolicy();
    }

    /** @test */
    public function admin_can_view_any_categories()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function read_write_user_can_view_any_categories()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_user_can_view_any_categories()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_cannot_view_any_categories()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($client));
    }

    /** @test */
    public function admin_can_view_category()
    {
        $admin = User::factory()->admin()->create();
        $category = Category::find('PAT') ?? Category::factory()->create(['code' => 'PAT']);

        $this->assertTrue($this->policy->view($admin, $category));
    }

    /** @test */
    public function read_only_user_can_view_category()
    {
        $user = User::factory()->readOnly()->create();
        $category = Category::find('PAT') ?? Category::factory()->create(['code' => 'PAT']);

        $this->assertTrue($this->policy->view($user, $category));
    }

    /** @test */
    public function client_cannot_view_category()
    {
        $client = User::factory()->client()->create();
        $category = Category::find('PAT') ?? Category::factory()->create(['code' => 'PAT']);

        $this->assertFalse($this->policy->view($client, $category));
    }

    /** @test */
    public function only_admin_can_create_category()
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
    public function only_admin_can_update_category()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $category = Category::find('PAT') ?? Category::factory()->create(['code' => 'PAT']);

        $this->assertTrue($this->policy->update($admin, $category));
        $this->assertFalse($this->policy->update($readWrite, $category));
    }

    /** @test */
    public function only_admin_can_delete_category()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $category = Category::find('PAT') ?? Category::factory()->create(['code' => 'PAT']);

        $this->assertTrue($this->policy->delete($admin, $category));
        $this->assertFalse($this->policy->delete($readWrite, $category));
    }
}
