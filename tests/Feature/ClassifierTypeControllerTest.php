<?php

namespace Tests\Feature;

use App\Models\ClassifierType;
use App\Models\User;
use Tests\TestCase;

class ClassifierTypeControllerTest extends TestCase
{
    protected User $adminUser;

    protected User $readWriteUser;

    protected User $readOnlyUser;

    protected User $clientUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users deterministically using factories
        $this->adminUser = User::factory()->admin()->create();
        $this->readWriteUser = User::factory()->readWrite()->create();
        $this->readOnlyUser = User::factory()->readOnly()->create();
        $this->clientUser = User::factory()->client()->create();
    }

    /**
     * Helper to create a classifier type for testing
     */
    protected function createClassifierType(string $code, string $name): ClassifierType
    {
        return ClassifierType::create([
            'code' => $code,
            'type' => ['en' => $name],
        ]);
    }

    /** @test */
    public function guest_cannot_access_classifier_types()
    {
        $response = $this->get(route('classifier_type.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function client_cannot_access_classifier_types()
    {
        $response = $this->actingAs($this->clientUser)->get(route('classifier_type.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_classifier_type_index()
    {
        $response = $this->actingAs($this->adminUser)->get(route('classifier_type.index'));

        $response->assertStatus(200);
        $response->assertViewIs('classifier_type.index');
    }

    /** @test */
    public function read_only_user_can_access_classifier_type_index()
    {
        $response = $this->actingAs($this->readOnlyUser)->get(route('classifier_type.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function read_write_user_can_access_classifier_type_index()
    {
        $response = $this->actingAs($this->readWriteUser)->get(route('classifier_type.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function classifier_type_index_returns_json_when_requested()
    {
        // Create a classifier type to ensure data exists
        $this->createClassifierType('TJSN', 'Test JSON Response');

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('classifier_type.index'));

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    /** @test */
    public function admin_can_create_classifier_type()
    {
        $response = $this->actingAs($this->adminUser)->get(route('classifier_type.create'));

        $response->assertStatus(200);
        $response->assertViewIs('classifier_type.create');
    }

    /** @test */
    public function admin_can_store_classifier_type()
    {
        $response = $this->actingAs($this->adminUser)->post(route('classifier_type.store'), [
            'code' => 'TCLA',
            'type' => 'Test Classifier Type',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('classifier_type', ['code' => 'TCLA']);
    }

    /** @test */
    public function read_write_user_cannot_store_classifier_type()
    {
        $response = $this->actingAs($this->readWriteUser)->post(route('classifier_type.store'), [
            'code' => 'NEWA',
            'type' => 'New Classifier',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('classifier_type', ['code' => 'NEWA']);
    }

    /** @test */
    public function admin_can_view_classifier_type()
    {
        $classifierType = $this->createClassifierType('TSHW', 'Test Show');

        $response = $this->actingAs($this->adminUser)->get(route('classifier_type.show', $classifierType));

        $response->assertStatus(200);
        $response->assertViewIs('classifier_type.show');
    }

    /** @test */
    public function admin_can_update_classifier_type()
    {
        $classifierType = $this->createClassifierType('TUPD', 'Test Update');

        $response = $this->actingAs($this->adminUser)->put(route('classifier_type.update', $classifierType), [
            'type' => 'Updated Type Name',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('classifier_type', ['code' => 'TUPD']);
        $classifierType->refresh();
        $this->assertEquals('Updated Type Name', $classifierType->getTranslation('type', 'en'));
    }

    /** @test */
    public function read_write_user_cannot_update_classifier_type()
    {
        $classifierType = $this->createClassifierType('NUPC', 'No Update');

        $response = $this->actingAs($this->readWriteUser)->put(route('classifier_type.update', $classifierType), [
            'type' => 'Changed Type',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_classifier_type()
    {
        $classifierType = $this->createClassifierType('TDEL', 'Test Delete');

        $response = $this->actingAs($this->adminUser)->delete(route('classifier_type.destroy', $classifierType));

        $response->assertSuccessful();
        $this->assertDatabaseMissing('classifier_type', ['code' => 'TDEL']);
    }

    /** @test */
    public function read_write_user_cannot_delete_classifier_type()
    {
        $classifierType = $this->createClassifierType('NDEL', 'No Delete');

        $response = $this->actingAs($this->readWriteUser)->delete(route('classifier_type.destroy', $classifierType));

        $response->assertStatus(403);
        $this->assertDatabaseHas('classifier_type', ['code' => 'NDEL']);
    }

    /** @test */
    public function classifier_type_index_can_be_filtered_by_code()
    {
        $match = $this->createClassifierType('TIT', 'Title Type');
        $noMatch = $this->createClassifierType('AUT', 'Author Type');

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('classifier_type.index', ['Code' => 'TIT']));

        $response->assertStatus(200)
            ->assertJsonFragment(['code' => 'TIT'])
            ->assertJsonMissing(['code' => 'AUT']);
    }

    /** @test */
    public function classifier_type_index_can_be_filtered_by_type()
    {
        $match = $this->createClassifierType('FTY', 'Filter Type');
        $noMatch = $this->createClassifierType('NFT', 'No Filter Type');

        $response = $this->actingAs($this->adminUser)
            ->getJson(route('classifier_type.index', ['Type' => 'Filter']));

        $response->assertStatus(200)
            ->assertJsonFragment(['code' => 'FTY'])
            ->assertJsonMissing(['code' => 'NFT']);
    }
}
