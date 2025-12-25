<?php

namespace Tests\Feature;

use App\Models\EventClassLnk;
use App\Models\EventName;
use App\Models\TemplateClass;
use App\Models\User;
use Tests\TestCase;

class EventClassControllerTest extends TestCase
{
    protected User $adminUser;

    protected User $readWriteUser;

    protected User $readOnlyUser;

    protected User $clientUser;

    protected EventName $eventName;

    protected TemplateClass $templateClass;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users deterministically using factories
        $this->adminUser = User::factory()->admin()->create();
        $this->readWriteUser = User::factory()->readWrite()->create();
        $this->readOnlyUser = User::factory()->readOnly()->create();
        $this->clientUser = User::factory()->client()->create();

        // Create required reference data
        $this->eventName = EventName::create([
            'code' => 'TEVA',
            'name' => ['en' => 'Test Event'],
            'is_task' => 0,
        ]);

        $this->templateClass = TemplateClass::create([
            'name' => 'Test Template Class',
        ]);
    }

    /**
     * Helper to create an event class link for testing
     */
    protected function createEventClassLink(array $attributes = []): EventClassLnk
    {
        return EventClassLnk::create(array_merge([
            'event_name_code' => $this->eventName->code,
            'template_class_id' => $this->templateClass->id,
        ], $attributes));
    }

    /** @test */
    public function guest_cannot_access_event_class_routes()
    {
        $response = $this->postJson(route('event-class.store'), []);

        $response->assertStatus(401);
    }

    /** @test */
    public function client_cannot_access_event_class_routes()
    {
        $response = $this->actingAs($this->clientUser)->postJson(route('event-class.store'), []);

        $response->assertStatus(403);
    }

    /** @test */
    public function read_write_user_can_store_event_class_link()
    {
        $response = $this->actingAs($this->readWriteUser)->postJson(route('event-class.store'), [
            'event_name_code' => $this->eventName->code,
            'template_class_id' => $this->templateClass->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('event_class_lnk', [
            'event_name_code' => $this->eventName->code,
            'template_class_id' => $this->templateClass->id,
        ]);
    }

    /** @test */
    public function admin_can_store_event_class_link()
    {
        // Create a different event name for admin test
        $adminEventName = EventName::create([
            'code' => 'TADM',
            'name' => ['en' => 'Test Admin Event'],
            'is_task' => 0,
        ]);

        $response = $this->actingAs($this->adminUser)->postJson(route('event-class.store'), [
            'event_name_code' => $adminEventName->code,
            'template_class_id' => $this->templateClass->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('event_class_lnk', [
            'event_name_code' => $adminEventName->code,
            'template_class_id' => $this->templateClass->id,
        ]);
    }

    /** @test */
    public function read_only_user_cannot_store_event_class_link()
    {
        $response = $this->actingAs($this->readOnlyUser)->postJson(route('event-class.store'), [
            'event_name_code' => $this->eventName->code,
            'template_class_id' => $this->templateClass->id,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function read_write_user_can_delete_event_class_link()
    {
        $eventClassLnk = $this->createEventClassLink();
        $eventClassLnkId = $eventClassLnk->id;

        $response = $this->actingAs($this->readWriteUser)->deleteJson(route('event-class.destroy', $eventClassLnk->id));

        $response->assertStatus(200);
        $response->assertJson(['success' => 'Link deleted']);

        // Verify database record was deleted
        $this->assertDatabaseMissing('event_class_lnk', [
            'id' => $eventClassLnkId,
        ]);
    }

    /** @test */
    public function read_only_user_cannot_delete_event_class_link()
    {
        $eventClassLnk = $this->createEventClassLink();

        $response = $this->actingAs($this->readOnlyUser)->deleteJson(route('event-class.destroy', $eventClassLnk->id));

        $response->assertStatus(403);

        // Verify record still exists
        $this->assertDatabaseHas('event_class_lnk', [
            'id' => $eventClassLnk->id,
        ]);
    }

    /** @test */
    public function deleting_nonexistent_event_class_link_returns_error()
    {
        $response = $this->actingAs($this->adminUser)->deleteJson(route('event-class.destroy', 999999));

        $response->assertStatus(200);
        $response->assertJson(['error' => 'Deletion failed']);
    }

    /** @test */
    public function store_filters_out_display_only_fields()
    {
        // Create a unique event name for this test
        $filterEventName = EventName::create([
            'code' => 'TFLT',
            'name' => ['en' => 'Test Filter Event'],
            'is_task' => 0,
        ]);

        // className is a display-only field that should be filtered out
        $response = $this->actingAs($this->readWriteUser)->postJson(route('event-class.store'), [
            'event_name_code' => $filterEventName->code,
            'template_class_id' => $this->templateClass->id,
            'className' => 'Some Display Name',
            '_token' => 'test-token',
            '_method' => 'POST',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('event_class_lnk', [
            'event_name_code' => $filterEventName->code,
            'template_class_id' => $this->templateClass->id,
        ]);
    }
}
