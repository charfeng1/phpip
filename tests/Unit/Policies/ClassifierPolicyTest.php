<?php

namespace Tests\Unit\Policies;

use App\Models\Classifier;
use App\Models\ClassifierType;
use App\Models\Matter;
use App\Models\User;
use App\Policies\ClassifierPolicy;
use Tests\TestCase;

class ClassifierPolicyTest extends TestCase
{
    protected ClassifierPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ClassifierPolicy();
    }

    /** @test */
    public function admin_can_view_any_classifiers()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($admin));
    }

    /** @test */
    public function read_write_user_can_view_any_classifiers()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_user_can_view_any_classifiers()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_can_view_any_classifiers()
    {
        $client = User::factory()->client()->create();

        $this->assertTrue($this->policy->viewAny($client));
    }

    /** @test */
    public function admin_can_view_classifier()
    {
        $admin = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);
        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test',
        ]);

        $this->assertTrue($this->policy->view($admin, $classifier));
    }

    /** @test */
    public function read_only_user_can_view_classifier()
    {
        $user = User::factory()->readOnly()->create();
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);
        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test',
        ]);

        $this->assertTrue($this->policy->view($user, $classifier));
    }

    /** @test */
    public function admin_can_create_classifier()
    {
        $admin = User::factory()->admin()->create();

        $this->assertTrue($this->policy->create($admin));
    }

    /** @test */
    public function read_write_user_can_create_classifier()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function read_only_user_cannot_create_classifier()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertFalse($this->policy->create($user));
    }

    /** @test */
    public function client_cannot_create_classifier()
    {
        $client = User::factory()->client()->create();

        $this->assertFalse($this->policy->create($client));
    }

    /** @test */
    public function admin_can_update_classifier()
    {
        $admin = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);
        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test',
        ]);

        $this->assertTrue($this->policy->update($admin, $classifier));
    }

    /** @test */
    public function read_write_user_can_update_classifier()
    {
        $user = User::factory()->readWrite()->create();
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);
        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test',
        ]);

        $this->assertTrue($this->policy->update($user, $classifier));
    }

    /** @test */
    public function read_only_user_cannot_update_classifier()
    {
        $user = User::factory()->readOnly()->create();
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);
        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test',
        ]);

        $this->assertFalse($this->policy->update($user, $classifier));
    }

    /** @test */
    public function admin_can_delete_classifier()
    {
        $admin = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);
        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test',
        ]);

        $this->assertTrue($this->policy->delete($admin, $classifier));
    }

    /** @test */
    public function client_cannot_delete_classifier()
    {
        $client = User::factory()->client()->create();
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);
        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test',
        ]);

        $this->assertFalse($this->policy->delete($client, $classifier));
    }
}
