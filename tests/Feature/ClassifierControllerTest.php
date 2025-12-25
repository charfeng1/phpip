<?php

namespace Tests\Feature;

use App\Models\Classifier;
use App\Models\ClassifierType;
use App\Models\Matter;
use App\Models\User;
use Tests\TestCase;

class ClassifierControllerTest extends TestCase
{
    /** @test */
    public function guest_cannot_access_classifiers()
    {
        $matter = Matter::factory()->create();

        $response = $this->get(route('matter.classifiers.index', $matter));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function admin_can_create_classifier()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $response = $this->actingAs($user)->post(route('matter.classifiers.store', $matter), [
            'type_code' => $classifierType->code,
            'value' => 'Test Title for Controller',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('classifier', [
            'matter_id' => $matter->id,
            'value' => 'Test Title for Controller',
        ]);
    }

    /** @test */
    public function read_write_user_can_create_classifier()
    {
        $user = User::factory()->readWrite()->create();
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $response = $this->actingAs($user)->post(route('matter.classifiers.store', $matter), [
            'type_code' => $classifierType->code,
            'value' => 'RW User Title',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function read_only_user_cannot_create_classifier()
    {
        $user = User::factory()->readOnly()->create();
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $response = $this->actingAs($user)->post(route('matter.classifiers.store', $matter), [
            'type_code' => $classifierType->code,
            'value' => 'Should Not Create',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_update_classifier()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);
        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Original Title',
        ]);

        $response = $this->actingAs($user)->put(
            route('matter.classifiers.update', [$matter, $classifier]),
            ['value' => 'Updated Title']
        );

        $response->assertRedirect();
    }

    /** @test */
    public function admin_can_delete_classifier()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);
        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'To Delete',
        ]);

        $response = $this->actingAs($user)->delete(
            route('matter.classifiers.destroy', [$matter, $classifier])
        );

        $response->assertRedirect();
    }

    /** @test */
    public function client_cannot_create_classifier()
    {
        $user = User::factory()->client()->create();
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $response = $this->actingAs($user)->post(route('matter.classifiers.store', $matter), [
            'type_code' => $classifierType->code,
            'value' => 'Client Title',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function classifier_requires_value()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $response = $this->actingAs($user)->post(route('matter.classifiers.store', $matter), [
            'type_code' => $classifierType->code,
            'value' => '', // Empty value
        ]);

        $response->assertSessionHasErrors('value');
    }
}
