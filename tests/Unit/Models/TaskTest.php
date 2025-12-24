<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\EventName;
use App\Models\Matter;
use App\Models\Rule;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class TaskTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();
    }

    /** @test */
    public function it_can_create_a_task()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertDatabaseHas('task', [
            'trigger_id' => $event->id,
        ]);
        $this->assertNotNull($task->id);
    }

    /** @test */
    public function it_belongs_to_a_trigger_event()
    {
        $event = Event::factory()->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertInstanceOf(Event::class, $task->trigger);
        $this->assertEquals($event->id, $task->trigger->id);
    }

    /** @test */
    public function it_has_matter_through_event()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->forEvent($event)->create();

        $this->assertInstanceOf(Matter::class, $task->matter);
        $this->assertEquals($matter->id, $task->matter->id);
    }

    /** @test */
    public function it_has_event_name_info()
    {
        $task = Task::factory()->renewal()->create();

        $this->assertNotNull($task->info);
        $this->assertEquals('REN', $task->info->code);
    }

    /** @test */
    public function it_can_be_a_renewal_task()
    {
        $task = Task::factory()->renewal()->create();

        $this->assertEquals('REN', $task->code);
    }

    /** @test */
    public function it_can_be_a_deadline_task()
    {
        $task = Task::factory()->deadline()->create();

        $this->assertEquals('DL', $task->code);
    }

    /** @test */
    public function it_can_be_pending()
    {
        $task = Task::factory()->pending()->create();

        $this->assertFalse((bool) $task->done);
        $this->assertNull($task->done_date);
    }

    /** @test */
    public function it_can_be_completed()
    {
        $task = Task::factory()->completed()->create();

        $this->assertTrue((bool) $task->done);
        $this->assertNotNull($task->done_date);
    }

    /** @test */
    public function it_can_be_overdue()
    {
        $task = Task::factory()->overdue()->create();

        $this->assertTrue($task->due_date->isPast());
        $this->assertFalse((bool) $task->done);
    }

    /** @test */
    public function it_can_be_due_soon()
    {
        $task = Task::factory()->dueSoon()->create();

        $this->assertTrue($task->due_date->isFuture());
        $this->assertTrue($task->due_date->diffInDays(now()) <= 30);
    }

    /** @test */
    public function it_can_have_due_date()
    {
        $task = Task::factory()->dueOn('2024-06-15')->create();

        $this->assertEquals('2024-06-15', $task->due_date->format('Y-m-d'));
    }

    /** @test */
    public function it_casts_due_date()
    {
        $task = Task::factory()->create(['due_date' => '2024-06-15']);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $task->due_date);
    }

    /** @test */
    public function it_casts_done_date()
    {
        $task = Task::factory()->completed()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $task->done_date);
    }

    /** @test */
    public function it_can_be_assigned_to_user()
    {
        $user = User::factory()->create(['login' => 'john.doe']);
        $task = Task::factory()->assignedTo('john.doe')->create();

        $this->assertEquals('john.doe', $task->assigned_to);
    }

    /** @test */
    public function it_can_have_cost_and_fee()
    {
        $task = Task::factory()->withFees(100.00, 250.00)->create();

        $this->assertEquals(100.00, $task->cost);
        $this->assertEquals(250.00, $task->fee);
    }

    /** @test */
    public function it_can_have_rule_used()
    {
        $rule = Rule::factory()->create();
        $task = Task::factory()->create(['rule_used' => $rule->id]);

        $this->assertEquals($rule->id, $task->rule_used);
        $this->assertInstanceOf(Rule::class, $task->rule);
    }

    /** @test */
    public function it_has_translatable_detail()
    {
        $task = Task::factory()->create([
            'detail' => json_encode(['en' => 'Year 5', 'fr' => 'Année 5']),
        ]);

        $this->assertContains('detail', $task->translatable);
    }

    /** @test */
    public function it_can_have_step()
    {
        $task = Task::factory()->create(['step' => 3]);

        $this->assertEquals(3, $task->step);
    }

    /** @test */
    public function it_can_have_grace_period()
    {
        $task = Task::factory()->create(['grace_period' => 6]);

        $this->assertEquals(6, $task->grace_period);
    }

    /** @test */
    public function it_can_have_invoice_step()
    {
        $task = Task::factory()->create(['invoice_step' => 2]);

        $this->assertEquals(2, $task->invoice_step);
    }

    /** @test */
    public function it_touches_parent_matter()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $originalUpdatedAt = $matter->updated_at;

        sleep(1);

        Task::factory()->forEvent($event)->create();
        $matter->refresh();

        $this->assertGreaterThanOrEqual($originalUpdatedAt, $matter->updated_at);
    }

    /** @test */
    public function it_hides_system_attributes()
    {
        $task = Task::factory()->create();
        $array = $task->toArray();

        $this->assertArrayNotHasKey('creator', $array);
        $this->assertArrayNotHasKey('updater', $array);
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('updated_at', $array);
    }

    /** @test */
    public function open_tasks_excludes_completed()
    {
        $matter = Matter::factory()->create(['dead' => false]);
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $pendingTask = Task::factory()->pending()->forEvent($event)->create();
        $completedTask = Task::factory()->completed()->forEvent($event)->create();

        $openTasks = $pendingTask->openTasks()->get();

        $this->assertTrue($openTasks->contains('id', $pendingTask->id));
        $this->assertFalse($openTasks->contains('id', $completedTask->id));
    }

    /** @test */
    public function open_tasks_excludes_dead_matters()
    {
        $liveMatter = Matter::factory()->create(['dead' => false]);
        $deadMatter = Matter::factory()->dead()->create();

        $liveEvent = Event::factory()->filing()->forMatter($liveMatter)->create();
        $deadEvent = Event::factory()->filing()->forMatter($deadMatter)->create();

        $liveTask = Task::factory()->pending()->forEvent($liveEvent)->create();
        $deadTask = Task::factory()->pending()->forEvent($deadEvent)->create();

        $openTasks = $liveTask->openTasks()->get();

        $this->assertTrue($openTasks->contains('id', $liveTask->id));
        $this->assertFalse($openTasks->contains('id', $deadTask->id));
    }

    /** @test */
    public function renewals_query_returns_builder()
    {
        $query = Task::renewals();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $query);
    }
}
