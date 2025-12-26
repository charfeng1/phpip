<?php

namespace App\Services;

use App\Enums\EventCode;
use App\Models\Task;
use App\Repositories\TaskRepository;
use Illuminate\Support\Collection;

/**
 * Handles renewal workflow state transitions.
 *
 * This service manages the state machine for renewal processing,
 * including transitions between workflow steps and invoice steps.
 * All transitions are logged via RenewalLogService.
 */
class RenewalWorkflowService
{
    /**
     * Workflow step constants.
     */
    public const STEP_PENDING = 0;

    public const STEP_FIRST_CALL = 2;

    public const STEP_TO_PAY = 4;

    public const STEP_CLEARED = 6;

    public const STEP_RECEIPT = 8;

    public const STEP_CLOSED = 10;

    public const STEP_ABANDONED = 12;

    public const STEP_LAPSED = 14;

    public const STEP_DONE = -1;

    /**
     * Invoice step constants.
     */
    public const INVOICE_NONE = 0;

    public const INVOICE_TO_INVOICE = 1;

    public const INVOICE_INVOICED = 2;

    public const INVOICE_PAID = 3;

    protected RenewalLogService $logService;

    protected TaskRepository $taskRepository;

    public function __construct(?RenewalLogService $logService = null, ?TaskRepository $taskRepository = null)
    {
        $this->logService = $logService ?? new RenewalLogService;
        $this->taskRepository = $taskRepository ?? app(TaskRepository::class);
    }

    /**
     * Transition renewals to "to pay" status.
     *
     * @param array $taskIds Array of task IDs to transition
     * @return int Number of updated tasks
     */
    public function markToPay(array $taskIds): int
    {
        if (empty($taskIds)) {
            return 0;
        }

        $renewals = $this->getRenewals($taskIds);
        $jobId = $this->logService->createJobId();

        // Build logs with invoice step transition (delegating to log service)
        $logs = $this->logService->buildTransitionLogs(
            $renewals,
            $jobId,
            self::STEP_TO_PAY,
            ['from_invoice_step' => self::INVOICE_NONE, 'to_invoice_step' => self::INVOICE_TO_INVOICE]
        );

        // Update tasks
        Task::whereIn('id', $taskIds)->update([
            'step' => self::STEP_TO_PAY,
            'invoice_step' => self::INVOICE_TO_INVOICE,
        ]);

        // Log transitions
        $this->logService->logBatch($logs);

        return count($taskIds);
    }

    /**
     * Mark renewals as invoiced.
     *
     * @param array $taskIds Array of task IDs
     * @return int Number of updated tasks
     */
    public function markInvoiced(array $taskIds): int
    {
        if (empty($taskIds)) {
            return 0;
        }

        Task::whereIn('id', $taskIds)->update(['invoice_step' => self::INVOICE_INVOICED]);

        return count($taskIds);
    }

    /**
     * Mark invoices as paid.
     *
     * @param array $taskIds Array of task IDs
     * @return int Number of updated tasks
     */
    public function markPaid(array $taskIds): int
    {
        if (empty($taskIds)) {
            return 0;
        }

        return Task::whereIn('id', $taskIds)->update(['invoice_step' => self::INVOICE_PAID]);
    }

    /**
     * Mark renewals as done/cleared.
     *
     * @param array $taskIds Array of task IDs
     * @return int Number of updated tasks
     */
    public function markDone(array $taskIds): int
    {
        if (empty($taskIds)) {
            return 0;
        }

        $renewals = $this->getRenewals($taskIds);
        $jobId = $this->logService->createJobId();
        $logs = [];
        $updated = 0;

        foreach ($renewals as $renewal) {
            $task = Task::find($renewal->id);
            if ($task === null) {
                continue;
            }

            $logs[] = [
                'task_id' => $renewal->id,
                'job_id' => $jobId,
                'from_step' => $renewal->step,
                'to_step' => self::STEP_CLEARED,
                'creator' => $this->logService->getUserLogin(),
                'created_at' => now(),
            ];

            $task->done = true;
            $task->done_date = now();
            $task->step = self::STEP_CLEARED;

            if ($task->save()) {
                $updated++;
            }
        }

        if (! empty($logs)) {
            $this->logService->logBatch($logs);
        }

        return $updated;
    }

    /**
     * Mark renewals as receipt received.
     *
     * @param array $taskIds Array of task IDs
     * @return int Number of updated tasks
     */
    public function markReceipt(array $taskIds): int
    {
        if (empty($taskIds)) {
            return 0;
        }

        $renewals = $this->getRenewals($taskIds);
        $jobId = $this->logService->createJobId();
        $logs = [];
        $updated = 0;

        foreach ($renewals as $renewal) {
            $task = Task::find($renewal->id);
            if ($task === null) {
                continue;
            }

            $logs[] = [
                'task_id' => $renewal->id,
                'job_id' => $jobId,
                'from_step' => $renewal->step,
                'to_step' => self::STEP_RECEIPT,
                'creator' => $this->logService->getUserLogin(),
                'created_at' => now(),
            ];

            $task->step = self::STEP_RECEIPT;

            if ($task->save()) {
                $updated++;
            }
        }

        if (! empty($logs)) {
            $this->logService->logBatch($logs);
        }

        return $updated;
    }

    /**
     * Close renewals.
     *
     * @param array $taskIds Array of task IDs
     * @return int Number of updated tasks
     */
    public function markClosed(array $taskIds): int
    {
        if (empty($taskIds)) {
            return 0;
        }

        $renewals = $this->getRenewals($taskIds);
        $jobId = $this->logService->createJobId();
        $logs = [];
        $updated = 0;

        foreach ($renewals as $renewal) {
            $task = Task::find($renewal->id);
            if ($task === null) {
                continue;
            }

            $toStep = $task->done ? self::STEP_DONE : self::STEP_CLOSED;

            $logs[] = [
                'task_id' => $renewal->id,
                'job_id' => $jobId,
                'from_step' => $task->step,
                'to_step' => $toStep,
                'from_done' => $task->done,
                'to_done' => 1,
                'creator' => $this->logService->getUserLogin(),
                'created_at' => now(),
            ];

            $task->step = $toStep;

            if ($task->save()) {
                $updated++;
            }
        }

        if (! empty($logs)) {
            $this->logService->logBatch($logs);
        }

        return $updated;
    }

    /**
     * Mark renewals as abandoned.
     *
     * Also creates an ABA (Abandoned) event on the matter.
     *
     * @param array $taskIds Array of task IDs
     * @return int Number of updated tasks
     */
    public function markAbandoned(array $taskIds): int
    {
        if (empty($taskIds)) {
            return 0;
        }

        $renewals = $this->getRenewals($taskIds);
        $jobId = $this->logService->createJobId();
        $logs = [];
        $updated = 0;

        foreach ($renewals as $renewal) {
            $task = Task::find($renewal->id);
            if ($task === null) {
                continue;
            }

            $logs[] = [
                'task_id' => $renewal->id,
                'job_id' => $jobId,
                'from_step' => $task->step,
                'to_step' => self::STEP_ABANDONED,
                'from_done' => $task->done,
                'to_done' => 1,
                'creator' => $this->logService->getUserLogin(),
                'created_at' => now(),
            ];

            $task->step = self::STEP_ABANDONED;

            if ($task->save()) {
                // Create abandoned event on the matter
                $task->matter->events()->create([
                    'code' => EventCode::ABANDONED->value,
                    'event_date' => now(),
                ]);
                $updated++;
            }
        }

        if (! empty($logs)) {
            $this->logService->logBatch($logs);
        }

        return $updated;
    }

    /**
     * Mark renewals as lapsed.
     *
     * Also creates a LAP (Lapsed) event on the matter.
     *
     * @param array $taskIds Array of task IDs
     * @return int Number of updated tasks
     */
    public function markLapsed(array $taskIds): int
    {
        if (empty($taskIds)) {
            return 0;
        }

        $renewals = $this->getRenewals($taskIds);
        $jobId = $this->logService->createJobId();
        $logs = [];
        $updated = 0;

        foreach ($renewals as $renewal) {
            $task = Task::find($renewal->id);
            if ($task === null) {
                continue;
            }

            $logs[] = [
                'task_id' => $renewal->id,
                'job_id' => $jobId,
                'from_step' => $task->step,
                'to_step' => self::STEP_LAPSED,
                'creator' => $this->logService->getUserLogin(),
                'created_at' => now(),
            ];

            $task->step = self::STEP_LAPSED;

            if ($task->save()) {
                // Create lapsed event on the matter
                $task->matter->events()->create([
                    'code' => EventCode::LAPSED->value,
                    'event_date' => now(),
                ]);
                $updated++;
            }
        }

        if (! empty($logs)) {
            $this->logService->logBatch($logs);
        }

        return $updated;
    }

    /**
     * Transition renewals to first call step.
     *
     * @param array $taskIds Array of task IDs
     * @return int Number of updated tasks
     */
    public function markFirstCall(array $taskIds): int
    {
        if (empty($taskIds)) {
            return 0;
        }

        Task::whereIn('id', $taskIds)->update(['step' => self::STEP_FIRST_CALL]);

        return count($taskIds);
    }

    /**
     * Transition renewals to grace period.
     *
     * @param array $taskIds Array of task IDs
     * @return int Number of updated tasks
     */
    public function markGracePeriod(array $taskIds): int
    {
        if (empty($taskIds)) {
            return 0;
        }

        Task::whereIn('id', $taskIds)->update(['grace_period' => 1]);

        return count($taskIds);
    }

    /**
     * Get renewals by IDs.
     *
     * @param array $taskIds Array of task IDs
     * @return Collection
     */
    protected function getRenewals(array $taskIds): Collection
    {
        return $this->taskRepository->renewalsByIds($taskIds)->get();
    }
}
