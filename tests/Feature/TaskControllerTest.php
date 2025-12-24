<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Matter;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class TaskControllerTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function guests_cannot_access_tasks()
    {
        $response = $this->get('/task');

        $response->assertRedirect('/login');
    }

    /** @test */
    public function authenticated_users_can_view_tasks_index()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get('/task');

        $response->assertStatus(200);
    }

    /** @test */
    public function tasks_index_shows_pending_tasks()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create(['dead' => false]);
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->pending()->forEvent($event)->create(['code' => 'DL']);

        $response = $this->actingAs($user)->get('/task');

        $response->assertStatus(200);
    }

    /** @test */
    public function tasks_can_be_filtered_by_user()
    {
        $user = User::factory()->admin()->create(['login' => 'admin.user']);
        $matter = Matter::factory()->create(['dead' => false]);
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->pending()->assignedTo('admin.user')->forEvent($event)->create();

        $response = $this->actingAs($user)->get('/task?user=admin.user');

        $response->assertStatus(200);
    }

    /** @test */
    public function task_can_be_shown()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $response = $this->actingAs($user)->get("/task/{$task->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function task_show_returns_json_when_requested()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $response = $this->actingAs($user)->getJson("/task/{$task->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'trigger_id',
            'code',
            'due_date',
        ]);
    }

    /** @test */
    public function admin_can_update_task()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->pending()->forEvent($event)->create();

        $response = $this->actingAs($user)->put("/task/{$task->id}", [
            'done' => 1,
            'done_date' => now()->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
    }

    /** @test */
    public function task_can_be_marked_done()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->pending()->forEvent($event)->create();

        $response = $this->actingAs($user)->put("/task/{$task->id}", [
            'done' => 1,
            'done_date' => '2024-01-15',
        ]);

        $task->refresh();
        $this->assertTrue((bool) $task->done);
    }

    /** @test */
    public function read_only_users_cannot_update_tasks()
    {
        $user = User::factory()->readOnly()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->pending()->forEvent($event)->create();

        $response = $this->actingAs($user)->put("/task/{$task->id}", [
            'done' => 1,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function admin_can_delete_task()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $response = $this->actingAs($user)->delete("/task/{$task->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('task', ['id' => $task->id]);
    }

    /** @test */
    public function renewals_page_is_accessible()
    {
        $user = User::factory()->admin()->create();

        $response = $this->actingAs($user)->get('/renewal');

        $response->assertStatus(200);
    }

    /** @test */
    public function renewals_shows_pending_renewals()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create(['dead' => false]);
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $renewal = Task::factory()->renewal()->pending()->forEvent($event)->create();

        $response = $this->actingAs($user)->get('/renewal');

        $response->assertStatus(200);
    }
}
