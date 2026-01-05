<?php

namespace Tests\Unit\Policies;

use App\Models\MatterType;
use App\Models\User;
use App\Policies\MatterTypePolicy;
use Tests\TestCase;

class MatterTypePolicyTest extends TestCase
{
    protected MatterTypePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new MatterTypePolicy;
    }

    /** @test */
    public function admin_can_view_any_matter_types()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function read_write_user_can_view_any_matter_types()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_user_can_view_any_matter_types()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_cannot_view_any_matter_types()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($client));
    }

    /** @test */
    public function admin_can_view_matter_type()
    {
        $admin = User::factory()->admin()->create();
        $matterType = MatterType::first() ?? MatterType::create([
            'code' => 'PRV',
            'type' => ['en' => 'Provisional'],
        ]);

        $this->assertTrue($this->policy->view($admin, $matterType));
    }

    /** @test */
    public function only_admin_can_create_matter_type()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->create($admin));
        $this->assertFalse($this->policy->create($readWrite));
    }

    /** @test */
    public function only_admin_can_update_matter_type()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $matterType = MatterType::first() ?? MatterType::create([
            'code' => 'PRV',
            'type' => ['en' => 'Provisional'],
        ]);

        $this->assertTrue($this->policy->update($admin, $matterType));
        $this->assertFalse($this->policy->update($readWrite, $matterType));
    }

    /** @test */
    public function only_admin_can_delete_matter_type()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $matterType = MatterType::first() ?? MatterType::create([
            'code' => 'PRV',
            'type' => ['en' => 'Provisional'],
        ]);

        $this->assertTrue($this->policy->delete($admin, $matterType));
        $this->assertFalse($this->policy->delete($readWrite, $matterType));
    }
}
