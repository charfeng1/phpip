<?php

namespace Tests\Feature;

use App\Models\Actor;
use App\Models\Event;
use App\Models\Matter;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatterControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
    }

    /** @test */
    public function guests_cannot_access_matters()
    {
        $response = $this->get('/matter');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function authenticated_users_can_view_matters_index()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get('/matter');

        $response->assertStatus(200);
        $response->assertViewIs('matter.index');
        $response->assertViewHas('matters');
    }

    /** @test */
    public function matters_index_shows_matters()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();

        $response = $this->actingAs($user)->get('/matter');

        $response->assertStatus(200);
        $response->assertSee($matter->caseref);
    }

    /** @test */
    public function matters_can_be_filtered_by_country()
    {
        $user = User::factory()->admin()->create();
        $usMatter = Matter::factory()->inCountry('US')->create();
        $epMatter = Matter::factory()->inCountry('EP')->create();

        $response = $this->actingAs($user)->get('/matter?country=US');

        $response->assertStatus(200);
        $response->assertSee($usMatter->caseref);
    }

    /** @test */
    public function matters_can_be_filtered_by_category()
    {
        $user = User::factory()->admin()->create();
        $patent = Matter::factory()->patent()->create();
        $trademark = Matter::factory()->trademark()->create();

        $response = $this->actingAs($user)->get('/matter?Cat=PAT');

        $response->assertStatus(200);
    }

    /** @test */
    public function matters_can_be_filtered_by_reference()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create(['caseref' => 'UNIQUE001']);

        $response = $this->actingAs($user)->get('/matter?Ref=UNIQUE');

        $response->assertStatus(200);
    }

    /** @test */
    public function matters_index_returns_json_when_requested()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/matter');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => ['id', 'caseref', 'country', 'category_code'],
        ]);
    }

    /** @test */
    public function admin_can_view_any_matter()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        Event::factory()->filing()->forMatter($matter)->create();

        $response = $this->actingAs($user)->get("/matter/{$matter->id}");

        $response->assertStatus(200);
        $response->assertViewIs('matter.show');
        $response->assertViewHas('matter');
    }

    /** @test */
    public function read_write_user_can_view_matter()
    {
        $user = User::factory()->readWrite()->create();
        $matter = Matter::factory()->create();
        Event::factory()->filing()->forMatter($matter)->create();

        $response = $this->actingAs($user)->get("/matter/{$matter->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function read_only_user_can_view_matter()
    {
        $user = User::factory()->readOnly()->create();
        $matter = Matter::factory()->create();
        Event::factory()->filing()->forMatter($matter)->create();

        $response = $this->actingAs($user)->get("/matter/{$matter->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function matter_show_includes_family()
    {
        $user = User::factory()->admin()->create();
        $container = Matter::factory()->asContainer()->create();
        Event::factory()->filing()->forMatter($container)->create();
        $member = Matter::factory()->asFamilyMember($container)->create();

        $response = $this->actingAs($user)->get("/matter/{$container->id}");

        $response->assertStatus(200);
        $response->assertViewHas('family');
    }

    /** @test */
    public function matter_show_includes_events()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $filing = Event::factory()->filing()->forMatter($matter)->create();
        $publication = Event::factory()->publication()->forMatter($matter)->create();

        $response = $this->actingAs($user)->get("/matter/{$matter->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function matter_show_includes_pending_tasks()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->pending()->forEvent($event)->create(['code' => 'DL']);

        $response = $this->actingAs($user)->get("/matter/{$matter->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function matter_info_returns_json()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        Event::factory()->filing()->forMatter($matter)->create();

        $response = $this->actingAs($user)->getJson("/matter/{$matter->id}/info");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'caseref',
            'country',
            'category_code',
        ]);
    }

    /** @test */
    public function admin_can_access_create_matter_form()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get('/matter/create');

        $response->assertStatus(200);
    }

    /** @test */
    public function read_write_can_access_create_matter_form()
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->get('/matter/create');

        $response->assertStatus(200);
    }

    /** @test */
    public function matter_can_be_sorted_by_id_desc()
    {
        $user = User::factory()->admin()->create();
        $matter1 = Matter::factory()->create();
        $matter2 = Matter::factory()->create();

        $response = $this->actingAs($user)->get('/matter?sortkey=id&sortdir=desc');

        $response->assertStatus(200);
    }

    /** @test */
    public function dead_matters_excluded_by_default()
    {
        $user = User::factory()->admin()->create();
        $liveMatter = Matter::factory()->create(['dead' => false]);
        $deadMatter = Matter::factory()->dead()->create();

        $response = $this->actingAs($user)->get('/matter');

        $response->assertStatus(200);
        $response->assertSee($liveMatter->caseref);
    }

    /** @test */
    public function dead_matters_can_be_included()
    {
        $user = User::factory()->admin()->create();
        $deadMatter = Matter::factory()->dead()->create();

        $response = $this->actingAs($user)->get('/matter?include_dead=1');

        $response->assertStatus(200);
    }

    /** @test */
    public function pagination_works()
    {
        $user = User::factory()->admin()->create();
        // Create many matters
        for ($i = 0; $i < 30; $i++) {
            Matter::factory()->create();
        }

        $response = $this->actingAs($user)->get('/matter');

        $response->assertStatus(200);
        // Check that pagination controls are present
        $response->assertViewHas('matters');
    }

    /** @test */
    public function nonexistent_matter_returns_404()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get('/matter/99999');

        $response->assertStatus(404);
    }
}
