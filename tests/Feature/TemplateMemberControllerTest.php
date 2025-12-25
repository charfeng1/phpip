<?php

namespace Tests\Feature;

use App\Models\TemplateClass;
use App\Models\TemplateMember;
use App\Models\User;
use Tests\TestCase;

class TemplateMemberControllerTest extends TestCase
{
    /** @test */
    public function guest_cannot_access_template_members()
    {
        $response = $this->get(route('template-member.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function client_cannot_access_template_members()
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->get(route('template-member.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_template_member_index()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('template-member.index'));

        $response->assertStatus(200);
        $response->assertViewIs('template-members.index');
    }

    /** @test */
    public function read_only_user_can_access_template_member_index()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->get(route('template-member.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function read_write_user_can_access_template_member_index()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->get(route('template-member.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function template_member_index_returns_json_when_requested()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->getJson(route('template-member.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function template_member_index_can_be_filtered_by_summary()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('template-member.index', ['summary' => 'Letter']));

        $response->assertStatus(200);
    }

    /** @test */
    public function template_member_index_can_be_filtered_by_language()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('template-member.index', ['language' => 'en']));

        $response->assertStatus(200);
    }

    /** @test */
    public function template_member_index_can_be_filtered_by_category()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('template-member.index', ['category' => 'General']));

        $response->assertStatus(200);
    }

    /** @test */
    public function template_member_index_can_be_filtered_by_style()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('template-member.index', ['style' => 'Formal']));

        $response->assertStatus(200);
    }

    /** @test */
    public function template_member_index_can_be_filtered_by_class()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('template-member.index', ['class' => 'Letters']));

        $response->assertStatus(200);
    }

    /** @test */
    public function template_member_index_can_be_filtered_by_format()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('template-member.index', ['format' => 'odt']));

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_create_template_member()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get(route('template-member.create'));

        $response->assertStatus(200);
        $response->assertViewIs('template-members.create');
    }

    /** @test */
    public function read_write_user_cannot_access_create_template_member()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->get(route('template-member.create'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_store_template_member()
    {
        $user = User::factory()->admin()->create();

        $templateClass = TemplateClass::first();
        if (!$templateClass) {
            $templateClass = TemplateClass::create([
                'name' => 'Test Class for Store',
            ]);
        }

        $response = $this->actingAs($user)->post(route('template-member.store'), [
            'summary' => 'Test Template',
            'language' => 'en',
            'template_class_id' => $templateClass->id,
            'format' => 'odt',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('template_members', ['summary' => 'Test Template']);
    }

    /** @test */
    public function read_write_user_cannot_store_template_member()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->post(route('template-member.store'), [
            'summary' => 'New Template',
            'language' => 'en',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_view_template_member()
    {
        $user = User::factory()->admin()->create();

        $templateClass = TemplateClass::first();
        if (!$templateClass) {
            $templateClass = TemplateClass::create([
                'name' => 'Test Class for View',
            ]);
        }

        $templateMember = TemplateMember::create([
            'summary' => 'Test View Template',
            'language' => 'en',
            'template_class_id' => $templateClass->id,
            'format' => 'odt',
        ]);

        $response = $this->actingAs($user)->get(route('template-member.show', $templateMember));

        $response->assertStatus(200);
        $response->assertViewIs('template-members.show');
    }

    /** @test */
    public function read_only_user_can_view_template_member()
    {
        $user = User::factory()->readOnly()->create();

        $templateClass = TemplateClass::first();
        if (!$templateClass) {
            $templateClass = TemplateClass::create([
                'name' => 'Test Class for RO View',
            ]);
        }

        $templateMember = TemplateMember::create([
            'summary' => 'Test RO View Template',
            'language' => 'en',
            'template_class_id' => $templateClass->id,
            'format' => 'odt',
        ]);

        $response = $this->actingAs($user)->get(route('template-member.show', $templateMember));

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_update_template_member()
    {
        $user = User::factory()->admin()->create();

        $templateClass = TemplateClass::first();
        if (!$templateClass) {
            $templateClass = TemplateClass::create([
                'name' => 'Test Class for Update',
            ]);
        }

        $templateMember = TemplateMember::create([
            'summary' => 'Original Summary',
            'language' => 'en',
            'template_class_id' => $templateClass->id,
            'format' => 'odt',
        ]);

        $response = $this->actingAs($user)->put(route('template-member.update', $templateMember), [
            'summary' => 'Updated Summary',
        ]);

        $response->assertSuccessful();
    }

    /** @test */
    public function read_write_user_cannot_update_template_member()
    {
        $user = User::factory()->readWrite()->create();

        $templateClass = TemplateClass::first();
        if (!$templateClass) {
            $templateClass = TemplateClass::create([
                'name' => 'Test Class for RW Update',
            ]);
        }

        $templateMember = TemplateMember::create([
            'summary' => 'No Update Summary',
            'language' => 'en',
            'template_class_id' => $templateClass->id,
            'format' => 'odt',
        ]);

        $response = $this->actingAs($user)->put(route('template-member.update', $templateMember), [
            'summary' => 'Changed Summary',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_template_member()
    {
        $user = User::factory()->admin()->create();

        $templateClass = TemplateClass::first();
        if (!$templateClass) {
            $templateClass = TemplateClass::create([
                'name' => 'Test Class for Delete',
            ]);
        }

        $templateMember = TemplateMember::create([
            'summary' => 'To Delete Template',
            'language' => 'en',
            'template_class_id' => $templateClass->id,
            'format' => 'odt',
        ]);

        $response = $this->actingAs($user)->delete(route('template-member.destroy', $templateMember));

        $response->assertStatus(200);
        $response->assertJson(['success' => 'Template deleted']);
    }

    /** @test */
    public function read_write_user_cannot_delete_template_member()
    {
        $user = User::factory()->readWrite()->create();

        $templateClass = TemplateClass::first();
        if (!$templateClass) {
            $templateClass = TemplateClass::create([
                'name' => 'Test Class for RW Delete',
            ]);
        }

        $templateMember = TemplateMember::create([
            'summary' => 'No Delete Template',
            'language' => 'en',
            'template_class_id' => $templateClass->id,
            'format' => 'odt',
        ]);

        $response = $this->actingAs($user)->delete(route('template-member.destroy', $templateMember));

        $response->assertStatus(403);
    }
}
