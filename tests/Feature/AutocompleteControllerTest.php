<?php

namespace Tests\Feature;

use App\Enums\ActorRole;
use App\Models\Actor;
use App\Models\Category;
use App\Models\ClassifierType;
use App\Models\Country;
use App\Models\EventName;
use App\Models\Matter;
use App\Models\Role;
use App\Models\TemplateClass;
use App\Models\User;
use Tests\TestCase;

class AutocompleteControllerTest extends TestCase
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

        // Create users with different roles using factories (deterministic)
        $this->adminUser = User::factory()->admin()->create();
        $this->readWriteUser = User::factory()->readWrite()->create();
        $this->readOnlyUser = User::factory()->readOnly()->create();
        $this->clientUser = User::factory()->client()->create();

        // Create required reference data using factories
        $this->country = Country::factory()->create();
        $this->category = Category::factory()->create();
    }

    /** @test */
    public function guest_cannot_access_autocomplete_routes()
    {
        $response = $this->getJson('/user/autocomplete?term=test');

        $response->assertStatus(401);
    }

    /** @test */
    public function client_cannot_access_autocomplete_routes()
    {
        $response = $this->actingAs($this->clientUser)
            ->getJson('/user/autocomplete?term=test');

        $response->assertStatus(403);
    }

    /** @test */
    public function read_only_user_cannot_access_autocomplete_routes()
    {
        $response = $this->actingAs($this->readOnlyUser)
            ->getJson('/user/autocomplete?term=test');

        $response->assertStatus(403);
    }

    /** @test */
    public function read_write_user_can_access_matter_autocomplete_and_receives_valid_json()
    {
        $matter = Matter::factory()->create([
            'category_code' => $this->category->code,
            'country' => $this->country->iso,
            'caseref' => 'AUTOTEST001',
        ]);

        $response = $this->actingAs($this->readWriteUser)
            ->getJson(route('matter.autocomplete', ['term' => 'AUTOTEST']));

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonFragment(['value' => $matter->uid]);
    }

    /** @test */
    public function matter_autocomplete_returns_empty_array_when_no_matches()
    {
        $response = $this->actingAs($this->readWriteUser)
            ->getJson(route('matter.autocomplete', ['term' => 'NONEXISTENT99999']));

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonCount(0);
    }

    /** @test */
    public function read_write_user_can_access_new_caseref_autocomplete()
    {
        $response = $this->actingAs($this->readWriteUser)
            ->getJson(route('matter.new-caseref', ['term' => 'NEW']));

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonStructure([
                '*' => ['key', 'value'],
            ]);
    }

    /** @test */
    public function read_write_user_can_access_event_name_autocomplete()
    {
        $eventName = EventName::factory()->create([
            'code' => 'TAUTO',
            'name' => ['en' => 'Test Autocomplete Event'],
            'is_task' => false,
        ]);

        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/event-name/autocomplete/0?term=TAUTO');

        $response->assertStatus(200)
            ->assertJsonIsArray();
    }

    /** @test */
    public function read_write_user_can_access_classifier_type_autocomplete()
    {
        $classifierType = ClassifierType::create([
            'code' => 'TAUT',
            'type' => ['en' => 'Test Autocomplete Type'],
        ]);

        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/classifier-type/autocomplete/0?term=TAUT');

        $response->assertStatus(200)
            ->assertJsonIsArray();
    }

    /** @test */
    public function read_write_user_can_access_user_autocomplete()
    {
        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/user/autocomplete?term=test');

        $response->assertStatus(200)
            ->assertJsonIsArray();
    }

    /** @test */
    public function read_write_user_can_access_user_by_id_autocomplete()
    {
        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/user/autocomplete-by-id?term=test');

        $response->assertStatus(200)
            ->assertJsonIsArray();
    }

    /** @test */
    public function read_write_user_can_access_actor_autocomplete()
    {
        $actor = Actor::factory()->create([
            'name' => 'Unique Autocomplete Actor',
            'country' => $this->country->iso,
        ]);

        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/actor/autocomplete?term=Unique Autocomplete');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonFragment(['value' => 'Unique Autocomplete Actor']);
    }

    /** @test */
    public function actor_autocomplete_can_include_create_option()
    {
        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/actor/autocomplete/create?term=BrandNewActorName');

        $response->assertStatus(200)
            ->assertJsonIsArray();

        // When using create option, response should include entries
        $json = $response->json();
        $this->assertIsArray($json);
    }

    /** @test */
    public function read_write_user_can_access_role_autocomplete()
    {
        $role = Role::factory()->create([
            'code' => 'TST',
            'name' => ['en' => 'Test Autocomplete Role'],
        ]);

        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/role/autocomplete?term=Test Autocomplete');

        $response->assertStatus(200)
            ->assertJsonIsArray();
    }

    /** @test */
    public function read_write_user_can_access_dbrole_autocomplete()
    {
        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/dbrole/autocomplete?term=DB');

        $response->assertStatus(200)
            ->assertJsonIsArray();
    }

    /** @test */
    public function read_write_user_can_access_country_autocomplete()
    {
        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/country/autocomplete?term=' . substr($this->country->name, 0, 3));

        $response->assertStatus(200)
            ->assertJsonIsArray();
    }

    /** @test */
    public function read_write_user_can_access_category_autocomplete()
    {
        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/category/autocomplete?term=' . substr($this->category->category ?? 'Pat', 0, 3));

        $response->assertStatus(200)
            ->assertJsonIsArray();
    }

    /** @test */
    public function read_write_user_can_access_type_autocomplete()
    {
        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/type/autocomplete?term=Nat');

        $response->assertStatus(200)
            ->assertJsonIsArray();
    }

    /** @test */
    public function read_write_user_can_access_template_category_autocomplete()
    {
        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/template-category/autocomplete?term=General');

        $response->assertStatus(200)
            ->assertJsonIsArray();
    }

    /** @test */
    public function read_write_user_can_access_template_class_autocomplete()
    {
        TemplateClass::create(['name' => 'Autocomplete Test Class']);

        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/template-class/autocomplete?term=Autocomplete Test');

        $response->assertStatus(200)
            ->assertJsonIsArray();
    }

    /** @test */
    public function read_write_user_can_access_template_style_autocomplete()
    {
        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/template-style/autocomplete?term=formal');

        $response->assertStatus(200)
            ->assertJsonIsArray();
    }

    /** @test */
    public function user_autocomplete_returns_correct_format()
    {
        $targetUser = User::factory()->create([
            'name' => 'Searchable Autocomplete User',
            'login' => 'searchauto',
        ]);

        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/user/autocomplete?term=Searchable Autocomplete');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonFragment([
                'value' => 'Searchable Autocomplete User',
                'key' => 'searchauto',
            ]);
    }

    /** @test */
    public function user_by_id_autocomplete_returns_id_as_key()
    {
        $targetUser = User::factory()->create([
            'name' => 'User By Id Autocomplete Test',
            'login' => 'userbyidauto',
        ]);

        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/user/autocomplete-by-id?term=User By Id Autocomplete');

        $response->assertStatus(200)
            ->assertJsonIsArray()
            ->assertJsonFragment([
                'value' => 'User By Id Autocomplete Test',
                'key' => $targetUser->id,
            ]);
    }

    /** @test */
    public function admin_can_access_all_autocomplete_routes()
    {
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
            $response = $this->actingAs($this->adminUser)->getJson($route);
            $response->assertStatus(200)
                ->assertJsonIsArray();
        }
    }

    /** @test */
    public function user_by_id_validates_term_parameter()
    {
        $longTerm = str_repeat('a', 300);

        $response = $this->actingAs($this->readWriteUser)
            ->getJson('/user/autocomplete-by-id?term=' . $longTerm);

        $response->assertStatus(422);
    }
}
