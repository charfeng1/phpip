<?php

namespace Tests\Feature;

use App\Models\TemplateClass;
use App\Models\TemplateMember;
use App\Models\User;
use Tests\TestCase;

class TemplateMemberControllerTest extends TestCase
{
    protected User $adminUser;

    protected User $readWriteUser;

    protected User $readOnlyUser;

    protected User $clientUser;

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
        $this->templateClass = TemplateClass::create([
            'name' => 'Test Template Class',
        ]);
    }

    /**
     * Helper to create a template member for testing
     */
    protected function createTemplateMember(array $attributes = []): TemplateMember
    {
        return TemplateMember::create(array_merge([
            'summary' => 'Test Template',
            'language' => 'en',
            'class_id' => $this->templateClass->id,
            'format' => 'odt',
        ], $attributes));
    }

    /** @test */
    public function guest_cannot_access_template_members()
    {
        $response = $this->get(route('template-member.index'));

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function client_cannot_access_template_members()
    {
        $response = $this->actingAs($this->clientUser)->get(route('template-member.index'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_template_member_index()
    {
        $response = $this->actingAs($this->adminUser)->get(route('template-member.index'));

        $response->assertStatus(200);
        $response->assertViewIs('template-members.index');
    }

    /** @test */
    public function read_only_user_can_access_template_member_index()
    {
        $response = $this->actingAs($this->readOnlyUser)->get(route('template-member.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function read_write_user_can_access_template_member_index()
    {
        $response = $this->actingAs($this->readWriteUser)->get(route('template-member.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function template_member_index_returns_json_when_requested()
    {
        // Create a template member to ensure data exists
        $this->createTemplateMember(['summary' => 'JSON Test Template']);

        $response = $this->actingAs($this->adminUser)->getJson(route('template-member.index'));

        $response->assertStatus(200);
        $response->assertJsonIsArray();
    }

    /** @test */
    public function template_member_index_can_be_filtered_by_summary()
    {
        $response = $this->actingAs($this->adminUser)->get(route('template-member.index', ['summary' => 'Letter']));

        $response->assertStatus(200);
    }

    /** @test */
    public function template_member_index_can_be_filtered_by_language()
    {
        $response = $this->actingAs($this->adminUser)->get(route('template-member.index', ['language' => 'en']));

        $response->assertStatus(200);
    }

    /** @test */
    public function template_member_index_can_be_filtered_by_category()
    {
        $response = $this->actingAs($this->adminUser)->get(route('template-member.index', ['category' => 'General']));

        $response->assertStatus(200);
    }

    /** @test */
    public function template_member_index_can_be_filtered_by_style()
    {
        $response = $this->actingAs($this->adminUser)->get(route('template-member.index', ['style' => 'Formal']));

        $response->assertStatus(200);
    }

    /** @test */
    public function template_member_index_can_be_filtered_by_class()
    {
        $response = $this->actingAs($this->adminUser)->get(route('template-member.index', ['class' => 'Letters']));

        $response->assertStatus(200);
    }

    /** @test */
    public function template_member_index_can_be_filtered_by_format()
    {
        $response = $this->actingAs($this->adminUser)->get(route('template-member.index', ['format' => 'odt']));

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_create_template_member()
    {
        $response = $this->actingAs($this->adminUser)->get(route('template-member.create'));

        $response->assertStatus(200);
        $response->assertViewIs('template-members.create');
    }

    /** @test */
    public function read_write_user_cannot_access_create_template_member()
    {
        $response = $this->actingAs($this->readWriteUser)->get(route('template-member.create'));

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_store_template_member()
    {
        $response = $this->actingAs($this->adminUser)->post(route('template-member.store'), [
            'summary' => 'New Stored Template',
            'language' => 'en',
            'class_id' => $this->templateClass->id,
            'format' => 'odt',
        ]);

        $response->assertSuccessful();
        $this->assertDatabaseHas('template_members', ['summary' => 'New Stored Template']);
    }

    /** @test */
    public function read_write_user_cannot_store_template_member()
    {
        $response = $this->actingAs($this->readWriteUser)->post(route('template-member.store'), [
            'summary' => 'Blocked Template',
            'language' => 'en',
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('template_members', ['summary' => 'Blocked Template']);
    }

    /** @test */
    public function admin_can_view_template_member()
    {
        $templateMember = $this->createTemplateMember(['summary' => 'View Test Template']);

        $response = $this->actingAs($this->adminUser)->get(route('template-member.show', $templateMember));

        $response->assertStatus(200);
        $response->assertViewIs('template-members.show');
    }

    /** @test */
    public function read_only_user_can_view_template_member()
    {
        $templateMember = $this->createTemplateMember(['summary' => 'RO View Template']);

        $response = $this->actingAs($this->readOnlyUser)->get(route('template-member.show', $templateMember));

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_update_template_member()
    {
        $templateMember = $this->createTemplateMember(['summary' => 'Original Summary']);

        $response = $this->actingAs($this->adminUser)->put(route('template-member.update', $templateMember), [
            'summary' => 'Updated Summary',
        ]);

        $response->assertSuccessful();

        // Verify database was updated
        $this->assertDatabaseHas('template_members', [
            'id' => $templateMember->id,
            'summary' => 'Updated Summary',
        ]);
    }

    /** @test */
    public function read_write_user_cannot_update_template_member()
    {
        $templateMember = $this->createTemplateMember(['summary' => 'No Update Summary']);

        $response = $this->actingAs($this->readWriteUser)->put(route('template-member.update', $templateMember), [
            'summary' => 'Changed Summary',
        ]);

        $response->assertStatus(403);

        // Verify database was NOT updated
        $this->assertDatabaseHas('template_members', [
            'id' => $templateMember->id,
            'summary' => 'No Update Summary',
        ]);
    }

    /** @test */
    public function admin_can_delete_template_member()
    {
        $templateMember = $this->createTemplateMember(['summary' => 'To Delete Template']);
        $templateMemberId = $templateMember->id;

        $response = $this->actingAs($this->adminUser)->delete(route('template-member.destroy', $templateMember));

        $response->assertStatus(200);
        $response->assertJson(['success' => 'Template deleted']);

        // Verify database record was deleted
        $this->assertDatabaseMissing('template_members', [
            'id' => $templateMemberId,
        ]);
    }

    /** @test */
    public function read_write_user_cannot_delete_template_member()
    {
        $templateMember = $this->createTemplateMember(['summary' => 'No Delete Template']);

        $response = $this->actingAs($this->readWriteUser)->delete(route('template-member.destroy', $templateMember));

        $response->assertStatus(403);

        // Verify record still exists
        $this->assertDatabaseHas('template_members', [
            'id' => $templateMember->id,
        ]);
    }
}
