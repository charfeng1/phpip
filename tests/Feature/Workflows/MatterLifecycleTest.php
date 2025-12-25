<?php

namespace Tests\Feature\Workflows;

use App\Enums\EventCode;
use App\Models\Actor;
use App\Models\ActorPivot;
use App\Models\Category;
use App\Models\Classifier;
use App\Models\ClassifierType;
use App\Models\Country;
use App\Models\Event;
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
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        $response = $this->actingAs($user)->post(route('matter.store'), [
            'category_code' => $category->code,
            'country' => $country->iso,
            'caseref' => 'LIFECYCLE001',
            'responsible' => $user->login,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('matter', ['caseref' => 'LIFECYCLE001']);
    }

    /** @test */
    public function matter_can_have_filing_event_added()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
            'caseref' => 'FILING001',
        ]);

        $eventName = EventName::where('code', 'FIL')->first();
        if (!$eventName) {
            $eventName = EventName::create([
                'code' => 'FIL',
                'name' => ['en' => 'Filing'],
                'is_task' => 0,
            ]);
        }

        $response = $this->actingAs($user)->postJson(route('event.store'), [
            'matter_id' => $matter->id,
            'code' => $eventName->code,
            'event_date' => now()->format('Y-m-d'),
            'detail' => 'TEST/2024/001',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('event', [
            'matter_id' => $matter->id,
            'code' => 'FIL',
        ]);
    }

    /** @test */
    public function matter_can_have_actors_assigned()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);
        $role = Role::where('code', 'CLI')->first() ?? Role::factory()->create(['code' => 'CLI']);

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
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $classifierType = ClassifierType::where('code', 'TIT')->first();
        if (!$classifierType) {
            $classifierType = ClassifierType::create([
                'code' => 'TIT',
                'type' => ['en' => 'Title'],
            ]);
        }

        $response = $this->actingAs($user)->postJson(route('classifier.store'), [
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test Patent Title',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('classifier', [
            'matter_id' => $matter->id,
            'type_code' => 'TIT',
        ]);
    }

    /** @test */
    public function matter_details_can_be_viewed()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $response = $this->actingAs($user)->get(route('matter.show', $matter));

        $response->assertStatus(200);
        $response->assertViewIs('matter.show');
    }

    /** @test */
    public function matter_events_can_be_listed()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $response = $this->actingAs($user)->get(route('matter.events', $matter));

        $response->assertStatus(200);
    }

    /** @test */
    public function matter_can_be_updated()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
            'caseref' => 'UPDATE001',
        ]);

        $response = $this->actingAs($user)->put(route('matter.update', $matter), [
            'notes' => 'Updated notes for the matter',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function matter_tasks_can_be_listed()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $response = $this->actingAs($user)->get(route('matter.tasks', $matter));

        $response->assertStatus(200);
    }

    /** @test */
    public function matter_renewals_can_be_listed()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $response = $this->actingAs($user)->get(route('matter.renewals', $matter));

        $response->assertStatus(200);
    }

    /** @test */
    public function client_can_only_view_their_own_matters()
    {
        $clientUser = User::factory()->client()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        // Create a matter not linked to this client
        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $response = $this->actingAs($clientUser)->get(route('matter.show', $matter));

        // Client should be forbidden from viewing matters they're not linked to
        $response->assertStatus(403);
    }
}
