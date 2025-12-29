<?php

namespace Tests\Feature;

use App\Enums\EventCode;
use App\Models\Event;
use App\Models\Matter;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * Test the welcome page loads.
     */
    public function test_welcome_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * Test the dashboard requires authentication.
     */
    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/home');

        $response->assertRedirect('/login');
    }

    /**
     * Test authenticated user can access dashboard.
     */
    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->readWrite()->create();

        $response = $this->actingAs($user)->get('/home');

        $response->assertStatus(200);
        $response->assertViewHas('categories');
        $response->assertViewHas('taskscount');
        $response->assertViewHas('tasks');
        $response->assertViewHas('renewals');
    }

    /**
     * Test dashboard user filter works with CHAR column padding.
     *
     * This tests a regression where actor.login is CHAR(16) and gets padded
     * with spaces. When filtering tasks by user_dashboard parameter, the
     * comparison must work regardless of padding.
     */
    public function test_dashboard_user_filter_handles_char_column_padding(): void
    {
        // Create a user - the login will be stored as CHAR(16) with padding
        $user = User::factory()->readWrite()->create([
            'login' => 'testuser',  // Will be stored as 'testuser        ' (padded to 16 chars)
        ]);

        // Create a matter with the user as responsible
        $matter = Matter::factory()->create([
            'responsible' => trim($user->login),  // Use trimmed login
        ]);

        // Create a filing event
        $event = Event::factory()->create([
            'matter_id' => $matter->id,
            'code' => EventCode::FILING->value,
        ]);

        // Create a task assigned to the user (using trimmed login)
        $task = Task::factory()->pending()->create([
            'trigger_id' => $event->id,
            'code' => 'REP',
            'assigned_to' => trim($user->login),  // Use trimmed login
        ]);

        // Test filtering by user_dashboard parameter
        // The filter should match even though actor.login has padding
        $response = $this->actingAs($user)->get('/home?user_dashboard=' . trim($user->login));

        $response->assertStatus(200);

        // Verify the task is included in the filtered results
        $tasks = $response->viewData('tasks');
        $this->assertTrue(
            $tasks->contains('id', $task->id),
            'Task should be visible when filtering by assigned user'
        );
    }

    /**
     * Test dashboard separates renewals from other tasks.
     */
    public function test_dashboard_separates_renewals_from_tasks(): void
    {
        $user = User::factory()->readWrite()->create();

        $matter = Matter::factory()->create();
        $event = Event::factory()->create([
            'matter_id' => $matter->id,
            'code' => EventCode::FILING->value,
        ]);

        // Create a regular task
        $regularTask = Task::factory()->pending()->create([
            'trigger_id' => $event->id,
            'code' => 'REP',
        ]);

        // Create a renewal task
        $renewalTask = Task::factory()->pending()->renewal()->create([
            'trigger_id' => $event->id,
        ]);

        $response = $this->actingAs($user)->get('/home');

        $response->assertStatus(200);

        $tasks = $response->viewData('tasks');
        $renewals = $response->viewData('renewals');

        // Regular task should be in tasks, not renewals
        $this->assertTrue($tasks->contains('id', $regularTask->id));
        $this->assertFalse($renewals->contains('id', $regularTask->id));

        // Renewal task should be in renewals, not tasks
        $this->assertTrue($renewals->contains('id', $renewalTask->id));
        $this->assertFalse($tasks->contains('id', $renewalTask->id));
    }

    /**
     * Test clear tasks endpoint requires authentication.
     */
    public function test_clear_tasks_requires_authentication(): void
    {
        $response = $this->post('matter/clear-tasks', [
            'task_ids' => [1],
            'done_date' => '2025-01-01',
        ]);

        $response->assertRedirect('/login');
    }
}
