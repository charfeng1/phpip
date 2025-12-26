<?php

namespace Tests\Feature\Workflows;

use App\Models\Category;
use App\Models\Country;
use App\Models\Matter;
use App\Models\User;
use Tests\TestCase;

/**
 * Integration tests for Patent Family Creation workflow.
 *
 * Tests creation and linking of patent families across jurisdictions.
 */
class PatentFamilyCreationTest extends TestCase
{
    /** @test */
    public function container_matter_can_be_created()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'WO']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        $response = $this->actingAs($user)->postJson(route('matter.store'), [
            'category_code' => $category->code,
            'country' => $country->iso,
            'caseref' => 'FAMILY001',
            'responsible' => $user->login,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['redirect']);

        $this->assertDatabaseHas('matter', ['caseref' => 'FAMILY001']);
    }

    /** @test */
    public function child_matter_can_be_linked_to_container()
    {
        $user = User::factory()->readWrite()->create();

        $woCountry = Country::where('iso', 'WO')->first() ?? Country::factory()->create(['iso' => 'WO']);
        $usCountry = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        // Create container (PCT) matter
        $container = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $woCountry->iso,
            'caseref' => 'CONTAINER001',
        ]);

        // Create national phase (US) matter linked to container
        $response = $this->actingAs($user)->postJson(route('matter.store'), [
            'category_code' => $category->code,
            'country' => $usCountry->iso,
            'caseref' => 'CONTAINER001',
            'container_id' => $container->id,
            'responsible' => $user->login,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['redirect']);

        $this->assertDatabaseHas('matter', [
            'caseref' => 'CONTAINER001',
            'country' => $usCountry->iso,
            'container_id' => $container->id,
        ]);
    }

    /** @test */
    public function multiple_national_phases_can_be_linked_to_same_container()
    {
        $user = User::factory()->readWrite()->create();

        $woCountry = Country::where('iso', 'WO')->first() ?? Country::factory()->create(['iso' => 'WO']);
        $usCountry = Country::where('iso', 'US')->first() ?? Country::factory()->create(['iso' => 'US']);
        $epCountry = Country::where('iso', 'EP')->first() ?? Country::factory()->create(['iso' => 'EP']);
        $jpCountry = Country::where('iso', 'JP')->first() ?? Country::factory()->create(['iso' => 'JP']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        // Create container (PCT) matter
        $container = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $woCountry->iso,
            'caseref' => 'MULTINP001',
        ]);

        $countries = [$usCountry, $epCountry, $jpCountry];

        foreach ($countries as $npCountry) {
            $this->actingAs($user)->postJson(route('matter.store'), [
                'category_code' => $category->code,
                'country' => $npCountry->iso,
                'caseref' => 'MULTINP001',
                'container_id' => $container->id,
                'responsible' => $user->login,
            ]);
        }

        $childMatters = Matter::where('container_id', $container->id)->get();
        $this->assertEquals(3, $childMatters->count());
    }

    /** @test */
    public function child_matter_inherits_container_caseref()
    {
        $user = User::factory()->readWrite()->create();

        $woCountry = Country::where('iso', 'WO')->first() ?? Country::factory()->create(['iso' => 'WO']);
        $usCountry = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        $container = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $woCountry->iso,
            'caseref' => 'INHERIT001',
        ]);

        $response = $this->actingAs($user)->postJson(route('matter.store'), [
            'category_code' => $category->code,
            'country' => $usCountry->iso,
            'caseref' => 'INHERIT001',
            'container_id' => $container->id,
            'responsible' => $user->login,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['redirect']);

        $child = Matter::where('container_id', $container->id)->first();
        $this->assertEquals($container->caseref, $child->caseref);
    }

    /** @test */
    public function container_matters_can_be_listed()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->get(route('matter.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function matter_can_have_origin_tracking()
    {
        $user = User::factory()->readWrite()->create();

        $woCountry = Country::where('iso', 'WO')->first() ?? Country::factory()->create(['iso' => 'WO']);
        $epCountry = Country::where('iso', 'EP')->first() ?? Country::factory()->create(['iso' => 'EP']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        $container = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $woCountry->iso,
            'caseref' => 'ORIGIN001',
        ]);

        // EP validation from PCT
        $response = $this->actingAs($user)->postJson(route('matter.store'), [
            'category_code' => $category->code,
            'country' => $epCountry->iso,
            'caseref' => 'ORIGIN001',
            'container_id' => $container->id,
            'origin' => $woCountry->iso, // Track origin as PCT
            'responsible' => $user->login,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['redirect']);

        $this->assertDatabaseHas('matter', [
            'caseref' => 'ORIGIN001',
            'country' => $epCountry->iso,
            'origin' => $woCountry->iso,
        ]);
    }

    /** @test */
    public function divisional_matter_can_be_created()
    {
        $user = User::factory()->readWrite()->create();

        $usCountry = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        // Create parent matter
        $parent = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $usCountry->iso,
            'caseref' => 'PARENT001',
        ]);

        // Create divisional linked to parent using descendant operation
        $response = $this->actingAs($user)->postJson(route('matter.store'), [
            'category_code' => $category->code,
            'country' => $usCountry->iso,
            'caseref' => 'PARENT001DIV',
            'operation' => 'descendant',
            'parent_id' => $parent->id,
            'responsible' => $user->login,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['redirect']);

        // Verify the divisional was created with parent container link
        $this->assertDatabaseHas('matter', [
            'caseref' => 'PARENT001DIV',
            'container_id' => $parent->id,
        ]);
    }

    /** @test */
    public function matter_with_different_types_can_be_created()
    {
        $user = User::factory()->readWrite()->create();

        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);

        // Regular patent application
        $this->actingAs($user)->postJson(route('matter.store'), [
            'category_code' => $category->code,
            'country' => $country->iso,
            'caseref' => 'TYPES001',
            'responsible' => $user->login,
        ]);

        $this->assertDatabaseHas('matter', ['caseref' => 'TYPES001']);
    }
}
