<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Country;
use App\Models\Matter;
use App\Models\User;
use Tests\TestCase;

class MatterSearchControllerTest extends TestCase
{
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
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'TEST',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function search_by_ref_with_single_result_redirects_to_matter()
    {
        $user = User::factory()->readWrite()->create();

        // Create a matter with unique caseref
        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
            'caseref' => 'UNIQUEREF123',
        ]);

        $response = $this->actingAs($user)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'UNIQUEREF123',
        ]);

        $response->assertRedirect('matter/' . $matter->id);
    }

    /** @test */
    public function search_by_ref_with_multiple_results_redirects_to_list()
    {
        $user = User::factory()->readWrite()->create();

        // Create multiple matters with similar caseref
        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
            'caseref' => 'MULTI001',
        ]);

        Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
            'caseref' => 'MULTI002',
        ]);

        $response = $this->actingAs($user)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'MULTI',
        ]);

        $response->assertRedirect('/matter?Ref=MULTI');
    }

    /** @test */
    public function search_by_other_field_redirects_to_list()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->post(route('matter.search'), [
            'search_field' => 'Client',
            'matter_search' => 'Test Client',
        ]);

        $response->assertRedirect('/matter?Client=Test Client');
    }

    /** @test */
    public function search_by_ref_with_no_results_redirects_to_list()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'NONEXISTENT12345',
        ]);

        // When no results, it still redirects to list view with filters
        $response->assertRedirect('/matter?Ref=NONEXISTENT12345');
    }

    /** @test */
    public function client_user_can_search_matters()
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'TEST',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function admin_can_search_matters()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'TEST',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function read_only_user_can_search_matters()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'TEST',
        ]);

        $response->assertRedirect();
    }

    /** @test */
    public function search_preserves_search_parameters()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->post(route('matter.search'), [
            'search_field' => 'Title',
            'matter_search' => 'My Patent Title',
        ]);

        $response->assertRedirect('/matter?Title=My Patent Title');
    }

    /** @test */
    public function search_with_special_characters()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->post(route('matter.search'), [
            'search_field' => 'Ref',
            'matter_search' => 'TEST-001/A',
        ]);

        $response->assertRedirect();
    }
}
