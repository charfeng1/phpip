<?php

namespace Tests\Feature;

use App\Models\ClassifierType;
use App\Models\User;
use Tests\TestCase;

class ClassifierTypeControllerTest extends TestCase
{
    /** @test */
    public function guest_cannot_access_classifier_types()
    {
        $response = $this->get(route('classifier_type.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function client_cannot_access_classifier_types()
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->get(route('classifier_type.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_classifier_type_index()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('classifier_type.index'));

        $response->assertStatus(200);
        $response->assertViewIs('classifier_type.index');
    }

    /** @test */
    public function read_only_user_can_access_classifier_type_index()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->get(route('classifier_type.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function read_write_user_can_access_classifier_type_index()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->get(route('classifier_type.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function classifier_type_index_returns_json_when_requested()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)
            ->getJson(route('classifier_type.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function admin_can_create_classifier_type()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('classifier_type.create'));

        $response->assertStatus(200);
        $response->assertViewIs('classifier_type.create');
    }

    /** @test */
    public function admin_can_store_classifier_type()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post(route('classifier_type.store'), [
            'code' => 'TCLA',
            'type' => 'Test Classifier Type',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('classifier_type', ['code' => 'TCLA']);
    }

    /** @test */
    public function read_write_user_cannot_store_classifier_type()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->post(route('classifier_type.store'), [
            'code' => 'NEWA',
            'type' => 'New Classifier',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_classifier_type()
    {
        $user = User::factory()->admin()->create();

        $classifierType = ClassifierType::first();
        if (!$classifierType) {
            $classifierType = ClassifierType::create([
                'code' => 'TSHW',
                'type' => ['en' => 'Test Show'],
            ]);
        }

        $response = $this->actingAs($user)->get(route('classifier_type.show', $classifierType));

        $response->assertStatus(200);
        $response->assertViewIs('classifier_type.show');
    }

    /** @test */
    public function admin_can_update_classifier_type()
    {
        $user = User::factory()->admin()->create();

        $classifierType = ClassifierType::create([
            'code' => 'TUPD',
            'type' => ['en' => 'Test Update'],
        ]);

        $response = $this->actingAs($user)->put(route('classifier_type.update', $classifierType), [
            'type' => 'Updated Type Name',
        ]);

        $response->assertSuccessful();
    }

    /** @test */
    public function read_write_user_cannot_update_classifier_type()
    {
        $user = User::factory()->readWrite()->create();

        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'NUPC',
            'type' => ['en' => 'No Update'],
        ]);

        $response = $this->actingAs($user)->put(route('classifier_type.update', $classifierType), [
            'type' => 'Changed Type',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_classifier_type()
    {
        $user = User::factory()->admin()->create();

        $classifierType = ClassifierType::create([
            'code' => 'TDEL',
            'type' => ['en' => 'Test Delete'],
        ]);

        $response = $this->actingAs($user)->delete(route('classifier_type.destroy', $classifierType));

        $response->assertSuccessful();
        $this->assertDatabaseMissing('classifier_type', ['code' => 'TDEL']);
    }

    /** @test */
    public function read_write_user_cannot_delete_classifier_type()
    {
        $user = User::factory()->readWrite()->create();

        $classifierType = ClassifierType::create([
            'code' => 'NDEL',
            'type' => ['en' => 'No Delete'],
        ]);

        $response = $this->actingAs($user)->delete(route('classifier_type.destroy', $classifierType));

        $response->assertStatus(403);
    }

    /** @test */
    public function classifier_type_index_can_be_filtered_by_code()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('classifier_type.index', ['Code' => 'TIT']));

        $response->assertStatus(200);
    }

    /** @test */
    public function classifier_type_index_can_be_filtered_by_type()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('classifier_type.index', ['Type' => 'Title']));

        $response->assertStatus(200);
    }
}
