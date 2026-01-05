<?php

namespace Tests\Unit\Policies;

use App\Models\ClassifierType;
use App\Models\User;
use App\Policies\ClassifierTypePolicy;
use Tests\TestCase;

class ClassifierTypePolicyTest extends TestCase
{
    protected ClassifierTypePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ClassifierTypePolicy;
    }

    /** @test */
    public function admin_can_view_any_classifier_types()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function read_write_user_can_view_any_classifier_types()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_user_can_view_any_classifier_types()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_cannot_view_any_classifier_types()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($client));
    }

    /** @test */
    public function admin_can_view_classifier_type()
    {
        $admin = User::factory()->admin()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $this->assertTrue($this->policy->view($admin, $classifierType));
    }

    /** @test */
    public function only_admin_can_create_classifier_type()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->create($admin));
        $this->assertFalse($this->policy->create($readWrite));
    }

    /** @test */
    public function only_admin_can_update_classifier_type()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $this->assertTrue($this->policy->update($admin, $classifierType));
        $this->assertFalse($this->policy->update($readWrite, $classifierType));
    }

    /** @test */
    public function only_admin_can_delete_classifier_type()
    {
        $admin = User::factory()->admin()->create();
        $readWrite = User::factory()->readWrite()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $this->assertTrue($this->policy->delete($admin, $classifierType));
        $this->assertFalse($this->policy->delete($readWrite, $classifierType));
    }
}
