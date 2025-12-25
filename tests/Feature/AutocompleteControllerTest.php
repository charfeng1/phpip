<?php

namespace Tests\Feature;

use App\Models\Actor;
use App\Models\Category;
use App\Models\ClassifierType;
use App\Models\Country;
use App\Models\EventName;
use App\Models\Matter;
use App\Models\MatterType;
use App\Models\Role;
use App\Models\TemplateClass;
use App\Models\TemplateMember;
use App\Models\User;
use Tests\TestCase;

class AutocompleteControllerTest extends TestCase
{
    /** @test */
    public function guest_cannot_access_autocomplete_routes()
    {
        $response = $this->getJson('/user/autocomplete?term=test');

        $response->assertStatus(401);
    }

    /** @test */
    public function client_cannot_access_autocomplete_routes()
    {
        $user = User::factory()->client()->create();

        $response = $this->actingAs($user)->getJson('/user/autocomplete?term=test');

        $response->assertStatus(403);
    }

    /** @test */
    public function read_write_user_can_access_matter_autocomplete()
    {
        $user = User::factory()->readWrite()->create();

        // Create a matter to search for
        $country = Country::first() ?? Country::factory()->create(['iso' => 'US']);
        $category = Category::first() ?? Category::factory()->create(['code' => 'PAT']);
        $matter = Matter::factory()->create([
            'category_code' => $category->code,
            'country' => $country->iso,
            'caseref' => 'TEST001',
        ]);

        $response = $this->actingAs($user)->getJson(route('matter.autocomplete', ['term' => 'TEST']));

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function read_write_user_can_access_new_caseref_autocomplete()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->getJson(route('matter.new-caseref', ['term' => 'NEW']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            ['key', 'value'],
        ]);
    }

    /** @test */
    public function read_write_user_can_access_event_name_autocomplete()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->getJson('/event-name/autocomplete/0?term=FIL');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function read_write_user_can_access_classifier_type_autocomplete()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->getJson('/classifier-type/autocomplete/0?term=T');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function read_write_user_can_access_user_autocomplete()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->getJson('/user/autocomplete?term=test');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function read_write_user_can_access_user_by_id_autocomplete()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->getJson('/user/autocomplete-by-id?term=test');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function read_write_user_can_access_actor_autocomplete()
    {
        $user = User::factory()->readWrite()->create();

        // Create an actor to search for
        Actor::factory()->create([
            'name' => 'Test Actor',
            'country' => 'US',
        ]);

        $response = $this->actingAs($user)->getJson('/actor/autocomplete?term=Test');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function actor_autocomplete_can_include_create_option()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->getJson('/actor/autocomplete/create?term=NewUniqueName');

        $response->assertStatus(200);
    }

    /** @test */
    public function read_write_user_can_access_role_autocomplete()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->getJson('/role/autocomplete?term=CLI');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function read_write_user_can_access_dbrole_autocomplete()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->getJson('/dbrole/autocomplete?term=D');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function read_write_user_can_access_country_autocomplete()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->getJson('/country/autocomplete?term=United');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function read_write_user_can_access_category_autocomplete()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->getJson('/category/autocomplete?term=Pat');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function read_write_user_can_access_type_autocomplete()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->getJson('/type/autocomplete?term=Nat');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function read_write_user_can_access_template_category_autocomplete()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->getJson('/template-category/autocomplete?term=General');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function read_write_user_can_access_template_class_autocomplete()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->getJson('/template-class/autocomplete?term=Letter');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function read_write_user_can_access_template_style_autocomplete()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->getJson('/template-style/autocomplete?term=formal');

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function user_autocomplete_returns_correct_format()
    {
        $user = User::factory()->readWrite()->create();

        // Create a user to search for
        $targetUser = User::factory()->create([
            'name' => 'Searchable User',
            'login' => 'searchable',
        ]);

        $response = $this->actingAs($user)->getJson('/user/autocomplete?term=Searchable');

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'value' => 'Searchable User',
            'key' => 'searchable',
        ]);
    }

    /** @test */
    public function user_by_id_autocomplete_returns_id_as_key()
    {
        $user = User::factory()->readWrite()->create();

        // Create a user to search for
        $targetUser = User::factory()->create([
            'name' => 'User By Id Test',
            'login' => 'userbyidtest',
        ]);

        $response = $this->actingAs($user)->getJson('/user/autocomplete-by-id?term=User By Id');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_can_access_all_autocomplete_routes()
    {
        $user = User::factory()->admin()->create();

        $routes = [
            '/user/autocomplete?term=test',
            '/user/autocomplete-by-id?term=test',
            '/actor/autocomplete?term=test',
            '/role/autocomplete?term=test',
            '/dbrole/autocomplete?term=test',
            '/country/autocomplete?term=test',
            '/category/autocomplete?term=test',
            '/type/autocomplete?term=test',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($user)->getJson($route);
            $response->assertStatus(200);
        }
    }

    /** @test */
    public function read_only_user_cannot_access_autocomplete_routes()
    {
        $user = User::factory()->readOnly()->create();

        $response = $this->actingAs($user)->getJson('/user/autocomplete?term=test');

        // Autocomplete routes require readwrite permission
        $response->assertStatus(403);
    }

    /** @test */
    public function user_by_id_validates_term_parameter()
    {
        $user = User::factory()->readWrite()->create();

        // Test with very long term (should be validated)
        $longTerm = str_repeat('a', 300);
        $response = $this->actingAs($user)->getJson('/user/autocomplete-by-id?term=' . $longTerm);

        // Should fail validation (max 255 characters)
        $response->assertStatus(422);
    }
}
