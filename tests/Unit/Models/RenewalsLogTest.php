<?php

namespace Tests\Unit\Models;

use App\Models\Event;
use App\Models\Matter;
use App\Models\RenewalsLog;
use App\Models\Task;
use App\Models\User;
use Tests\TestCase;

class RenewalsLogTest extends TestCase
{
    /** @test */
    public function it_can_create_a_renewals_log()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->renewal()->forEvent($event)->create();
        $user = User::factory()->create();

        $log = RenewalsLog::create([
            'task_id' => $task->id,
            'creator' => $user->login,
            'job_id' => 1,
            'from_step' => 0,
            'to_step' => 2,
        ]);

        $this->assertDatabaseHas('renewals_logs', [
            'task_id' => $task->id,
            'job_id' => 1,
        ]);
    }

    /** @test */
    public function it_belongs_to_a_task()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->renewal()->forEvent($event)->create();

        $log = RenewalsLog::create([
            'task_id' => $task->id,
            'job_id' => 2,
            'from_step' => 0,
            'to_step' => 2,
        ]);

        $this->assertInstanceOf(Task::class, $log->task);
        $this->assertEquals($task->id, $log->task->id);
    }

    /** @test */
    public function it_can_have_creator_info()
    {
        $user = User::factory()->create(['login' => 'renewal.user']);
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->renewal()->forEvent($event)->create();

        $log = RenewalsLog::create([
            'task_id' => $task->id,
            'creator' => 'renewal.user',
            'job_id' => 3,
            'from_step' => 0,
            'to_step' => 2,
        ]);

        $creatorInfo = $log->creatorInfo;

        $this->assertInstanceOf(User::class, $creatorInfo);
        $this->assertEquals('renewal.user', $creatorInfo->login);
    }

    /** @test */
    public function it_has_no_guarded_fields()
    {
        $log = new RenewalsLog;

        $this->assertEmpty($log->getGuarded());
    }

    /** @test */
    public function it_can_store_job_id()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->renewal()->forEvent($event)->create();

        $log = RenewalsLog::create([
            'task_id' => $task->id,
            'job_id' => 12345,
            'from_step' => 0,
            'to_step' => 2,
        ]);

        $this->assertEquals(12345, $log->job_id);
    }

    /** @test */
    public function it_can_store_step_transitions()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->renewal()->forEvent($event)->create();

        $log = RenewalsLog::create([
            'task_id' => $task->id,
            'job_id' => 4,
            'from_step' => 2,
            'to_step' => 4,
            'from_invoice_step' => 0,
            'to_invoice_step' => 1,
        ]);

        $this->assertEquals(2, $log->from_step);
        $this->assertEquals(4, $log->to_step);
        $this->assertEquals(0, $log->from_invoice_step);
        $this->assertEquals(1, $log->to_invoice_step);
    }

    /** @test */
    public function it_has_created_at_timestamp()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->renewal()->forEvent($event)->create();

        $log = RenewalsLog::create([
            'task_id' => $task->id,
            'job_id' => 5,
            'from_step' => 0,
            'to_step' => 2,
        ]);

        $this->assertNotNull($log->created_at);
    }

    /** @test */
    public function task_relationship_is_belongs_to()
    {
        $log = new RenewalsLog;

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $log->task()
        );
    }

    /** @test */
    public function creator_info_relationship_is_belongs_to()
    {
        $log = new RenewalsLog;

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $log->creatorInfo()
        );
    }
}
