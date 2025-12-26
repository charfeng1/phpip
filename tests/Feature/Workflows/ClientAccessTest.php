<?php

namespace Tests\Feature\Workflows;

use App\Enums\ActorRole;
use App\Enums\EventCode;
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
    protected User $clientUser;

    protected Country $country;

    protected Category $category;

    protected Role $clientRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Create client user deterministically
        $this->clientUser = User::factory()->client()->create();

        // Create required reference data
        $this->country = Country::factory()->create();
        $this->category = Category::factory()->create();
        // Use existing role from seed data or create new one if not present
        $this->clientRole = Role::firstOrCreate(
            ['code' => ActorRole::CLIENT->value],
            ['name' => json_encode(['en' => 'Client', 'fr' => 'Client']), 'shareable' => true]
        );
    }

    /**
     * Helper to create a matter for testing
     */
    protected function createMatter(array $attributes = []): Matter
    {
        return Matter::factory()->create(array_merge([
            'category_code' => $this->category->code,
            'country' => $this->country->iso,
        ], $attributes));
    }

    /**
     * Helper to link a client to a matter
     */
    protected function linkClientToMatter(User $client, Matter $matter): ActorPivot
    {
        return ActorPivot::create([
            'matter_id' => $matter->id,
            'actor_id' => $client->id,
            'role' => $this->clientRole->code,
            'shared' => 0,
            'display_order' => 1,
        ]);
    }

    /** @test */
    public function client_cannot_view_unrelated_matters()
    {
        // Create a matter not linked to this client
        $matter = $this->createMatter();

        $response = $this->actingAs($this->clientUser)->get(route('matter.show', $matter));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_can_view_their_own_matters()
    {
        // Create a matter and link client to it
        $matter = $this->createMatter();
        $this->linkClientToMatter($this->clientUser, $matter);

        $response = $this->actingAs($this->clientUser)->get(route('matter.show', $matter));

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_access_category_management()
    {
        $response = $this->actingAs($this->clientUser)->get(route('category.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_access_rule_management()
    {
        $response = $this->actingAs($this->clientUser)->get(route('rule.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_access_event_name_management()
    {
        $response = $this->actingAs($this->clientUser)->get(route('eventname.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_access_fee_management()
    {
        $response = $this->actingAs($this->clientUser)->get(route('fee.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_access_template_member_management()
    {
        $response = $this->actingAs($this->clientUser)->get(route('template-member.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_access_classifier_type_management()
    {
        $response = $this->actingAs($this->clientUser)->get(route('classifier_type.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_access_renewal_management()
    {
        $response = $this->actingAs($this->clientUser)->get(route('renewal.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_can_access_matter_search()
    {
        $response = $this->actingAs($this->clientUser)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'TEST',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function client_can_view_their_own_matter_events()
    {
        $matter = $this->createMatter();
        $this->linkClientToMatter($this->clientUser, $matter);

        $response = $this->actingAs($this->clientUser)->get(route('matter.events', $matter));

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_view_other_clients_matter_events()
    {
        // Create matter not linked to this client
        $matter = $this->createMatter();

        $response = $this->actingAs($this->clientUser)->get(route('matter.events', $matter));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_modify_actors()
    {
        $response = $this->actingAs($this->clientUser)->postJson(route('actor-pivot.store'), [
            'matter_id' => 1,
            'actor_id' => 1,
            'role' => ActorRole::CLIENT->value,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_modify_events()
    {
        $response = $this->actingAs($this->clientUser)->postJson(route('event.store'), [
            'matter_id' => 1,
            'code' => EventCode::FILING->value,
            'event_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function client_cannot_access_audit_logs()
    {
        $response = $this->actingAs($this->clientUser)->get(route('audit.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function client_can_access_home_page()
    {
        $response = $this->actingAs($this->clientUser)->get(route('home'));

        $response->assertStatus(200);
    }

    /** @test */
    public function client_cannot_access_autocomplete_endpoints()
    {
        $response = $this->actingAs($this->clientUser)->getJson('/user/autocomplete?term=test');

        $response->assertStatus(403);
    }
}
