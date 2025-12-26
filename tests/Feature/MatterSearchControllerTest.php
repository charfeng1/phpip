<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Country;
use App\Models\Matter;
use App\Models\User;
use Tests\TestCase;

class MatterSearchControllerTest extends TestCase
{
    protected User $adminUser;

    protected User $readWriteUser;

    protected User $readOnlyUser;

    protected User $clientUser;

    protected Country $country;

    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        // Create users deterministically using factories
        $this->adminUser = User::factory()->admin()->create();
        $this->readWriteUser = User::factory()->readWrite()->create();
        $this->readOnlyUser = User::factory()->readOnly()->create();
        $this->clientUser = User::factory()->client()->create();

        // Create required reference data
        $this->country = Country::factory()->create();
        $this->category = Category::factory()->create();
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

    /** @test */
    public function guest_cannot_use_matter_search()
    {
        $response = $this->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'TEST',
        ]);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function authenticated_user_can_search_matters()
    {
        $response = $this->actingAs($this->readWriteUser)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'TEST',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function search_by_ref_with_single_result_redirects_to_matter()
    {
        $matter = $this->createMatter(['caseref' => 'UNIQUEREF123']);

        $response = $this->actingAs($this->readWriteUser)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'UNIQUEREF123',
        ]);

        $response->assertRedirect(route('matter.show', $matter));
    }

    /** @test */
    public function search_by_ref_with_multiple_results_redirects_to_list()
    {
        $this->createMatter(['caseref' => 'MULTI001']);
        $this->createMatter(['caseref' => 'MULTI002']);

        $response = $this->actingAs($this->readWriteUser)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'MULTI',
        ]);

        $response->assertRedirect(route('matter.index', ['Ref' => 'MULTI']));
    }

    /** @test */
    public function search_by_other_field_redirects_to_list()
    {
        $response = $this->actingAs($this->readWriteUser)->post(route('matter.search'), [
            'search_field' => 'Client',
            'matter_search' => 'Test Client',
        ]);

        $response->assertRedirect(route('matter.index', ['Client' => 'Test Client']));
    }

    /** @test */
    public function search_by_ref_with_no_results_redirects_to_list()
    {
        $response = $this->actingAs($this->readWriteUser)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'NONEXISTENT12345',
        ]);

        // When no results, it still redirects to list view with filters
        $response->assertRedirect(route('matter.index', ['Ref' => 'NONEXISTENT12345']));
    }

    /** @test */
    public function client_user_can_search_matters()
    {
        $response = $this->actingAs($this->clientUser)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'TEST',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function admin_can_search_matters()
    {
        $response = $this->actingAs($this->adminUser)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'TEST',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function read_only_user_can_search_matters()
    {
        $response = $this->actingAs($this->readOnlyUser)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'TEST',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function search_preserves_search_parameters()
    {
        $response = $this->actingAs($this->readWriteUser)->post(route('matter.search'), [
            'search_field' => 'Title',
            'matter_search' => 'My Patent Title',
        ]);

        $response->assertRedirect(route('matter.index', ['Title' => 'My Patent Title']));
    }

    /** @test */
    public function search_with_special_characters()
    {
        $response = $this->actingAs($this->readWriteUser)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'TEST-001/A',
        ]);

        $response->assertRedirect();
    }
}
