<?php

namespace Tests\Unit\Policies;

use App\Models\Event;
use App\Models\Matter;
use App\Models\Task;
use App\Models\User;
use App\Policies\TaskPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected TaskPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed');
        $this->policy = new TaskPolicy();
    }

    // viewAny tests

    /** @test */
    public function admin_can_view_any_tasks()
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_write_can_view_any_tasks()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function read_only_can_view_any_tasks()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertTrue($this->policy->viewAny($user));
    }

    /** @test */
    public function client_cannot_view_any_tasks()
    {
        $user = User::factory()->client()->create();

        $this->assertFalse($this->policy->viewAny($user));
    }

    // view tests

    /** @test */
    public function admin_can_view_task()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertTrue($this->policy->view($user, $task));
    }

    /** @test */
    public function read_write_can_view_task()
    {
        $user = User::factory()->readWrite()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertTrue($this->policy->view($user, $task));
    }

    /** @test */
    public function read_only_can_view_task()
    {
        $user = User::factory()->readOnly()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertTrue($this->policy->view($user, $task));
    }

    /** @test */
    public function client_cannot_view_unrelated_task()
    {
        $user = User::factory()->client()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertFalse($this->policy->view($user, $task));
    }

    /** @test */
    public function client_can_view_own_matter_task()
    {
        $clientUser = User::factory()->client()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        // Create a matter-actor link with CLI role
        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $clientUser->id,
            'role' => 'CLI',
            'shared' => 0,
            'display_order' => 1,
        ]);

        $this->assertTrue($this->policy->view($clientUser, $task));
    }

    /** @test */
    public function user_with_no_role_can_view_own_matter_task()
    {
        $user = User::factory()->create(['default_role' => null]);
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        // Create a matter-actor link with CLI role
        \DB::table('matter_actor_lnk')->insert([
            'matter_id' => $matter->id,
            'actor_id' => $user->id,
            'role' => 'CLI',
            'shared' => 0,
            'display_order' => 1,
        ]);

        $this->assertTrue($this->policy->view($user, $task));
    }

    // create tests

    /** @test */
    public function admin_can_create_task()
    {
        $user = User::factory()->admin()->create();

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function read_write_can_create_task()
    {
        $user = User::factory()->readWrite()->create();

        $this->assertTrue($this->policy->create($user));
    }

    /** @test */
    public function read_only_cannot_create_task()
    {
        $user = User::factory()->readOnly()->create();

        $this->assertFalse($this->policy->create($user));
    }

    /** @test */
    public function client_cannot_create_task()
    {
        $user = User::factory()->client()->create();

        $this->assertFalse($this->policy->create($user));
    }

    // update tests

    /** @test */
    public function admin_can_update_task()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertTrue($this->policy->update($user, $task));
    }

    /** @test */
    public function read_write_can_update_task()
    {
        $user = User::factory()->readWrite()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertTrue($this->policy->update($user, $task));
    }

    /** @test */
    public function read_only_cannot_update_task()
    {
        $user = User::factory()->readOnly()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertFalse($this->policy->update($user, $task));
    }

    /** @test */
    public function client_cannot_update_task()
    {
        $user = User::factory()->client()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertFalse($this->policy->update($user, $task));
    }

    // delete tests

    /** @test */
    public function admin_can_delete_task()
    {
        $user = User::factory()->admin()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertTrue($this->policy->delete($user, $task));
    }

    /** @test */
    public function read_write_can_delete_task()
    {
        $user = User::factory()->readWrite()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertTrue($this->policy->delete($user, $task));
    }

    /** @test */
    public function read_only_cannot_delete_task()
    {
        $user = User::factory()->readOnly()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertFalse($this->policy->delete($user, $task));
    }

    /** @test */
    public function client_cannot_delete_task()
    {
        $user = User::factory()->client()->create();
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertFalse($this->policy->delete($user, $task));
    }
}
