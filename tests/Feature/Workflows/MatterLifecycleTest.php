<?php

namespace Tests\Feature\Workflows;

use App\Enums\ActorRole;
use App\Enums\CategoryCode;
use App\Enums\EventCode;
use App\Models\Actor;
use App\Models\Category;
use App\Models\Classifier;
use App\Models\ClassifierType;
use App\Models\Country;
use App\Models\EventName;
use App\Models\Matter;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

/**
 * Integration tests for Matter Lifecycle workflow.
 *
 * Tests the complete matter lifecycle from filing to grant/renewal.
 */
class MatterLifecycleTest extends TestCase
{
    /** @test */
    public function matter_can_be_created_with_required_fields()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => CategoryCode::PATENT->value]);

        $response = $this->actingAs($user)->postJson(route('matter.store'), [
            'category_code' => $category->code,
            'country' => $country->iso,
            'caseref' => 'LIFECYCLE001',
            'responsible' => $user->login,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['redirect']);

        $this->assertDatabaseHas('matter', ['caseref' => 'LIFECYCLE001']);
    }

    /** @test */
    public function matter_can_have_filing_event_added()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => CategoryCode::PATENT->value]);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
            'caseref' => 'FILING001',
        ]);

        $eventName = EventName::where('code', EventCode::FILING->value)->first();
        if (! $eventName) {
            $eventName = EventName::create([
                'code' => EventCode::FILING->value,
                'name' => ['en' => 'Filing'],
                'is_task' => 0,
            ]);
        }

        $response = $this->actingAs($user)->postJson(route('event.store'), [
            'matter_id' => $matter->id,
            'code' => $eventName->code,
            'eventName' => $eventName->code,  // Controller requires both code and eventName
            'event_date' => now()->isoFormat('L'),  // Controller expects locale date format (Y/m/d)
            'detail' => 'TEST/2024/001',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('event', [
            'matter_id' => $matter->id,
            'code' => EventCode::FILING->value,
        ]);
    }

    /** @test */
    public function matter_can_have_actors_assigned()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => CategoryCode::PATENT->value]);
        $role = Role::where('code', ActorRole::CLIENT->value)->first() ?? Role::factory()->create(['code' => ActorRole::CLIENT->value]);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $actor = Actor::factory()->create(['country' => $country->iso]);

        $response = $this->actingAs($user)->postJson(route('actor-pivot.store'), [
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $role->code,
            'shared' => 0,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('matter_actor_lnk', [
            'matter_id' => $matter->id,
            'actor_id' => $actor->id,
            'role' => $role->code,
        ]);
    }

    /** @test */
    public function matter_can_have_classifiers_added()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => CategoryCode::PATENT->value]);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        // Get or create a classifier type for the test
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TEST',
            'type' => ['en' => 'Test Type'],
        ]);

        $response = $this->actingAs($user)->post(route('classifier.store'), [
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test Classifier Value',
        ]);

        // Classifier store returns plain text ID with 200 status
        $response->assertStatus(200);
        $this->assertDatabaseHas('classifier', [
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test Classifier Value',
        ]);
    }
}
