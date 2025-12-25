<?php

namespace Tests\Feature;

use App\Models\EventClassLnk;
use App\Models\EventName;
use App\Models\TemplateClass;
use App\Models\User;
use Tests\TestCase;

class EventClassControllerTest extends TestCase
{
    /** @test */
    public function guest_cannot_access_event_class_routes()
    {
        $response = $this->postJson(route('event-class.store'), []);

        $response->assertStatus(401);
    }

    /** @test */
    public function client_cannot_access_event_class_routes()
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->postJson(route('event-class.store'), []);

        $response->assertStatus(403);
    }

    /** @test */
    public function read_write_user_can_store_event_class_link()
    {
        $user = User::factory()->readWrite()->create();

        // Create or find required related data
        $eventName = EventName::first();
        if (!$eventName) {
            $eventName = EventName::create([
                'code' => 'TFIL',
                'name' => ['en' => 'Test Filing'],
                'is_task' => 0,
            ]);
        }

        $templateClass = TemplateClass::first();
        if (!$templateClass) {
            $templateClass = TemplateClass::create([
                'name' => 'Test Template Class',
            ]);
        }

        $response = $this->actingAs($user)->postJson(route('event-class.store'), [
            'event_name_code' => $eventName->code,
            'template_class_id' => $templateClass->id,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('event_class_lnk', [
            'event_name_code' => $eventName->code,
            'template_class_id' => $templateClass->id,
        ]);
    }

    /** @test */
    public function admin_can_store_event_class_link()
    {
        $user = User::factory()->admin()->create();

        $eventName = EventName::first();
        if (!$eventName) {
            $eventName = EventName::create([
                'code' => 'TADM',
                'name' => ['en' => 'Test Admin Event'],
                'is_task' => 0,
            ]);
        }

        $templateClass = TemplateClass::first();
        if (!$templateClass) {
            $templateClass = TemplateClass::create([
                'name' => 'Admin Template Class',
            ]);
        }

        $response = $this->actingAs($user)->postJson(route('event-class.store'), [
            'event_name_code' => $eventName->code,
            'template_class_id' => $templateClass->id,
        ]);

        $response->assertStatus(201);
    }

    /** @test */
    public function read_only_user_cannot_store_event_class_link()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->postJson(route('event-class.store'), [
            'event_name_code' => 'FIL',
            'template_class_id' => 1,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function read_write_user_can_delete_event_class_link()
    {
        $user = User::factory()->readWrite()->create();

        // Create required related data
        $eventName = EventName::first();
        if (!$eventName) {
            $eventName = EventName::create([
                'code' => 'TDEL',
                'name' => ['en' => 'Test Delete Event'],
                'is_task' => 0,
            ]);
        }

        $templateClass = TemplateClass::first();
        if (!$templateClass) {
            $templateClass = TemplateClass::create([
                'name' => 'Delete Template Class',
            ]);
        }

        $eventClassLnk = EventClassLnk::create([
            'event_name_code' => $eventName->code,
            'template_class_id' => $templateClass->id,
        ]);

        $response = $this->actingAs($user)->deleteJson(route('event-class.destroy', $eventClassLnk->id));

        $response->assertStatus(200);
        $response->assertJson(['success' => 'Link deleted']);
    }

    /** @test */
    public function read_only_user_cannot_delete_event_class_link()
    {
        $user = User::factory()->readOnly()->create();

        // Create required related data
        $eventName = EventName::first();
        if (!$eventName) {
            $eventName = EventName::create([
                'code' => 'TNOD',
                'name' => ['en' => 'Test No Delete Event'],
                'is_task' => 0,
            ]);
        }

        $templateClass = TemplateClass::first();
        if (!$templateClass) {
            $templateClass = TemplateClass::create([
                'name' => 'No Delete Template Class',
            ]);
        }

        $eventClassLnk = EventClassLnk::create([
            'event_name_code' => $eventName->code,
            'template_class_id' => $templateClass->id,
        ]);

        $response = $this->actingAs($user)->deleteJson(route('event-class.destroy', $eventClassLnk->id));

        $response->assertStatus(403);
    }

    /** @test */
    public function deleting_nonexistent_event_class_link_returns_error()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->deleteJson(route('event-class.destroy', 999999));

        $response->assertStatus(200);
        $response->assertJson(['error' => 'Deletion failed']);
    }

    /** @test */
    public function store_filters_out_display_only_fields()
    {
        $user = User::factory()->readWrite()->create();

        $eventName = EventName::first();
        if (!$eventName) {
            $eventName = EventName::create([
                'code' => 'TFLT',
                'name' => ['en' => 'Test Filter Event'],
                'is_task' => 0,
            ]);
        }

        $templateClass = TemplateClass::first();
        if (!$templateClass) {
            $templateClass = TemplateClass::create([
                'name' => 'Filter Template Class',
            ]);
        }

        // className is a display-only field that should be filtered out
        $response = $this->actingAs($user)->postJson(route('event-class.store'), [
            'event_name_code' => $eventName->code,
            'template_class_id' => $templateClass->id,
            'className' => 'Some Display Name',
            '_token' => 'test-token',
            '_method' => 'POST',
        ]);

        $response->assertStatus(201);
    }
}
