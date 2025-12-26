<?php

namespace Tests\Unit\Services;

use App\Services\RenewalLogService;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class RenewalLogServiceTest extends TestCase
{
    protected RenewalLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        // Pass user login through constructor to avoid Auth facade dependency
        $this->service = new RenewalLogService('testuser');
    }

    public function test_build_notification_logs_for_first_call(): void
    {
        $renewals = new Collection([
            (object) ['id' => 1, 'step' => 0],
            (object) ['id' => 2, 'step' => 1],
        ]);

        $logs = $this->service->buildNotificationLogs($renewals, 1, 'first');

        $this->assertCount(2, $logs);
        $this->assertEquals(1, $logs[0]['task_id']);
        $this->assertEquals(0, $logs[0]['from_step']);
        $this->assertEquals(2, $logs[0]['to_step']);
        $this->assertEquals('testuser', $logs[0]['creator']);
        $this->assertNull($logs[0]['from_grace']);
        $this->assertNull($logs[0]['to_grace']);
    }

    public function test_build_notification_logs_for_warn_call(): void
    {
        $renewals = new Collection([
            (object) ['id' => 1, 'step' => 2],
        ]);

        $logs = $this->service->buildNotificationLogs($renewals, 1, 'warn');

        $this->assertNull($logs[0]['from_grace']);
        $this->assertNull($logs[0]['to_grace']);
    }

    public function test_build_notification_logs_for_last_call_sets_grace(): void
    {
        $renewals = new Collection([
            (object) ['id' => 1, 'step' => 2],
        ]);

        $logs = $this->service->buildNotificationLogs($renewals, 1, 'last');

        $this->assertEquals(0, $logs[0]['from_grace']);
        $this->assertEquals(1, $logs[0]['to_grace']);
    }

    public function test_build_transition_logs(): void
    {
        $renewals = new Collection([
            (object) ['id' => 1, 'step' => 2],
            (object) ['id' => 2, 'step' => 2],
        ]);

        $logs = $this->service->buildTransitionLogs($renewals, 1, 6);

        $this->assertCount(2, $logs);
        $this->assertEquals(6, $logs[0]['to_step']);
        $this->assertEquals(2, $logs[0]['from_step']);
        $this->assertEquals(1, $logs[0]['job_id']);
    }

    public function test_build_transition_logs_with_extra_fields(): void
    {
        $renewals = new Collection([
            (object) ['id' => 1, 'step' => 2],
        ]);

        $logs = $this->service->buildTransitionLogs($renewals, 1, 6, ['from_grace' => 0, 'to_grace' => 1]);

        $this->assertEquals(0, $logs[0]['from_grace']);
        $this->assertEquals(1, $logs[0]['to_grace']);
    }

    public function test_build_closing_logs_includes_invoice_steps(): void
    {
        $renewals = new Collection([
            (object) ['id' => 1, 'step' => 8, 'invoice_step' => 2],
        ]);

        $logs = $this->service->buildClosingLogs($renewals, 1, 14, 3);

        $this->assertEquals(8, $logs[0]['from_step']);
        $this->assertEquals(14, $logs[0]['to_step']);
        $this->assertEquals(2, $logs[0]['from_invoice_step']);
        $this->assertEquals(3, $logs[0]['to_invoice_step']);
    }

    public function test_build_closing_logs_handles_null_invoice_step(): void
    {
        $renewals = new Collection([
            (object) ['id' => 1, 'step' => 8, 'invoice_step' => null],
        ]);

        $logs = $this->service->buildClosingLogs($renewals, 1, 14, 3);

        $this->assertEquals(0, $logs[0]['from_invoice_step']);
    }

    public function test_build_notification_logs_empty_collection(): void
    {
        $renewals = new Collection([]);

        $logs = $this->service->buildNotificationLogs($renewals, 1, 'first');

        $this->assertCount(0, $logs);
    }
}
