<?php

namespace Tests\Feature\Workflows;

use App\Models\Actor;
use App\Models\ActorPivot;
use App\Models\Category;
use App\Models\Country;
use App\Models\Matter;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

/**
 * Integration tests for Client Access workflow.
 *
 * Tests that client users can only access their own matters and related data.
 */
class ClientAccessTest extends TestCase
{
    /** @test */
    public function client_cannot_view_unrelated_matters()
    {
        $client = User::factory()->client()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        // Create a matter not linked to this client
        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $response = $this->actingAs($client)->get(route('matter.show', $matter));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_can_view_their_own_matters()
    {
        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);
        $clientRole = Role::where('code', 'CLI')->first() ?? Role::factory()->create(['code' => 'CLI']);

        // Create client user (actor)
        $client = User::factory()->client()->create();

        // Create a matter
        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        // Link client to the matter
        ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $client->id,
            'role' => $clientRole->code,
            'shared' => 0,
            'display_order' => 1,
        ]);

        $response = $this->actingAs($client)->get(route('matter.show', $matter));

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_access_category_management()
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('category.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_access_rule_management()
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('rule.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_access_event_name_management()
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('eventname.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_access_fee_management()
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('fee.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_access_template_member_management()
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('template-member.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_access_classifier_type_management()
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('classifier_type.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_access_renewal_management()
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('renewal.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_can_access_matter_search()
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'TEST',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function client_can_view_their_own_matter_events()
    {
        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);
        $clientRole = Role::where('code', 'CLI')->first() ?? Role::factory()->create(['code' => 'CLI']);

        $client = User::factory()->client()->create();

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        // Link client to the matter
        ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $client->id,
            'role' => $clientRole->code,
            'shared' => 0,
            'display_order' => 1,
        ]);

        $response = $this->actingAs($client)->get(route('matter.events', $matter));

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_view_other_clients_matter_events()
    {
        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        $client = User::factory()->client()->create();

        // Create matter not linked to this client
        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
        ]);

        $response = $this->actingAs($client)->get(route('matter.events', $matter));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_modify_actors()
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->postJson(route('actor-pivot.store'), [
            'matter_id' => 1,
            'actor_id' => 1,
            'role' => 'CLI',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_modify_events()
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->postJson(route('event.store'), [
            'matter_id' => 1,
            'code' => 'FIL',
            'event_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_access_audit_logs()
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('audit.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_can_access_home_page()
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->get(route('home'));

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_access_autocomplete_endpoints()
    {
        $client = User::factory()->client()->create();

        $response = $this->actingAs($client)->getJson('/user/autocomplete?term=test');

        $response->assertStatus(403);
    }
}
