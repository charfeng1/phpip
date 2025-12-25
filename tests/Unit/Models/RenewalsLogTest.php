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
        $user = User::factory()->create();

        $log = RenewalsLog::create([
            'matter_id' => $matter->id,
            'creator' => $user->login,
            'job_id' => 'JOB-001',
        ]);

        $this->assertDatabaseHas('renewals_logs', [
            'matter_id' => $matter->id,
            'job_id' => 'JOB-001',
        ]);
    }

    /** @test */
    public function it_belongs_to_a_matter()
    {
        $matter = Matter::factory()->create();

        $log = RenewalsLog::create([
            'matter_id' => $matter->id,
            'job_id' => 'JOB-002',
        ]);

        $this->assertInstanceOf(Matter::class, $log->matter);
        $this->assertEquals($matter->id, $log->matter->id);
    }

    /** @test */
    public function it_can_belong_to_a_task()
    {
        $matter = Matter::factory()->create();
        $event = Event::factory()->filing()->forMatter($matter)->create();
        $task = Task::factory()->renewal()->forEvent($event)->create();

        $log = RenewalsLog::create([
            'matter_id' => $matter->id,
            'task_id' => $task->id,
            'job_id' => 'JOB-003',
        ]);

        $this->assertInstanceOf(Task::class, $log->task);
        $this->assertEquals($task->id, $log->task->id);
    }

    /** @test */
    public function it_can_have_creator_info()
    {
        $user = User::factory()->create(['login' => 'renewal.user']);
        $matter = Matter::factory()->create();

        $log = RenewalsLog::create([
            'matter_id' => $matter->id,
            'creator' => 'renewal.user',
            'job_id' => 'JOB-004',
        ]);

        $creatorInfo = $log->creatorInfo;

        $this->assertInstanceOf(User::class, $creatorInfo);
        $this->assertEquals('renewal.user', $creatorInfo->login);
    }

    /** @test */
    public function it_has_no_guarded_fields()
    {
        $log = new RenewalsLog();

        $this->assertEmpty($log->getGuarded());
    }

    /** @test */
    public function it_can_store_job_id()
    {
        $matter = Matter::factory()->create();

        $log = RenewalsLog::create([
            'matter_id' => $matter->id,
            'job_id' => 'UNIQUE-JOB-ID-12345',
        ]);

        $this->assertEquals('UNIQUE-JOB-ID-12345', $log->job_id);
    }

    /** @test */
    public function it_can_store_step_information()
    {
        $matter = Matter::factory()->create();

        $log = RenewalsLog::create([
            'matter_id' => $matter->id,
            'job_id' => 'JOB-005',
            'step' => 'invoice_sent',
        ]);

        $this->assertEquals('invoice_sent', $log->step);
    }

    /** @test */
    public function it_has_timestamps()
    {
        $matter = Matter::factory()->create();

        $log = RenewalsLog::create([
            'matter_id' => $matter->id,
            'job_id' => 'JOB-006',
        ]);

        $this->assertNotNull($log->created_at);
        $this->assertNotNull($log->updated_at);
    }

    /** @test */
    public function matter_relationship_is_belongs_to()
    {
        $log = new RenewalsLog();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $log->matter()
        );
    }

    /** @test */
    public function task_relationship_is_belongs_to()
    {
        $log = new RenewalsLog();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $log->task()
        );
    }

    /** @test */
    public function creator_info_relationship_is_belongs_to()
    {
        $log = new RenewalsLog();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $log->creatorInfo()
        );
    }
}
