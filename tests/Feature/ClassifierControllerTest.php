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
        $response = $this->get(route('classifier.index'));

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

        $response = $this->actingAs($user)->post(route('classifier.store'), [
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test Title for Controller',
        ]);

        $response->assertStatus(200);
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

        $response = $this->actingAs($user)->post(route('classifier.store'), [
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'RW User Title',
        ]);

        $response->assertStatus(200);
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

        $response = $this->actingAs($user)->post(route('classifier.store'), [
            'matter_id' => $matter->id,
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
            route('classifier.update', $classifier),
            ['value' => 'Updated Title']
        );

        $response->assertStatus(200);
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
            route('classifier.destroy', $classifier)
        );

        $response->assertStatus(200);
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

        $response = $this->actingAs($user)->post(route('classifier.store'), [
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Client Title',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function classifier_requires_value_or_image_or_link()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        // Value is required unless image or lnk_matter_id is provided
        $response = $this->actingAs($user)->postJson(route('classifier.store'), [
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            // No value, image, or lnk_matter_id
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('value');
    }
}
