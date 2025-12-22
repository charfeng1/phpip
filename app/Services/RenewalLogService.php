<?php

namespace App\Services;

use App\Models\RenewalsLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

/**
 * Handles logging of renewal workflow transitions.
 *
 * This service centralizes all logging logic for patent/trademark renewals,
 * providing audit trail for step changes, grace period transitions, and batch operations.
 */
class RenewalLogService
{
    /**
     * Create a new job ID for batch logging.
     *
     * @return int The new job ID
     */
    public function createJobId(): int
    {
        return (RenewalsLog::max('job_id') ?? 0) + 1;
    }

    /**
     * Get the current (latest) job ID.
     *
     * @return int The current job ID
     */
    public function getCurrentJobId(): int
    {
        return RenewalsLog::max('job_id') ?? 0;
    }

    /**
     * Log a single renewal step transition.
     *
     * @param int $taskId The task ID
     * @param int $jobId The job ID for grouping
     * @param int $fromStep The original step
     * @param int $toStep The new step
     * @param array $extra Additional log data (from_grace, to_grace, etc.)
     */
    public function logTransition(
        int $taskId,
        int $jobId,
        int $fromStep,
        int $toStep,
        array $extra = []
    ): void {
        RenewalsLog::create(array_merge([
            'task_id' => $taskId,
            'job_id' => $jobId,
            'from_step' => $fromStep,
            'to_step' => $toStep,
            'creator' => Auth::user()->login,
            'created_at' => now(),
        ], $extra));
    }

    /**
     * Log multiple renewal transitions in a batch.
     *
     * @param array $transitions Array of transition data
     * @param int $jobId The job ID for grouping
     */
    public function logBatch(array $transitions, int $jobId): void
    {
        if (empty($transitions)) {
            return;
        }

        RenewalsLog::insert($transitions);
    }

    /**
     * Build log entries for notification calls (step 0/1 -> 2).
     *
     * @param Collection $renewals The renewals being notified
     * @param int $jobId The job ID for grouping
     * @param string $notifyType The notification type (first, warn, last)
     * @return array The log entries ready for batch insert
     */
    public function buildNotificationLogs(Collection $renewals, int $jobId, string $notifyType): array
    {
        $logs = [];
        $fromGrace = ($notifyType === 'last') ? 0 : null;
        $toGrace = ($notifyType === 'last') ? 1 : null;
        $creator = Auth::user()->login;
        $now = now();

        foreach ($renewals as $renewal) {
            $logs[] = [
                'task_id' => $renewal->id,
                'job_id' => $jobId,
                'from_step' => $renewal->step,
                'to_step' => 2,
                'creator' => $creator,
                'created_at' => $now,
                'from_grace' => $fromGrace,
                'to_grace' => $toGrace,
            ];
        }

        return $logs;
    }

    /**
     * Build log entries for step transitions.
     *
     * @param Collection $renewals The renewals being transitioned
     * @param int $jobId The job ID for grouping
     * @param int $toStep The target step
     * @param array $extra Additional fields (from_invoice, to_invoice, etc.)
     * @return array The log entries ready for batch insert
     */
    public function buildTransitionLogs(
        Collection $renewals,
        int $jobId,
        int $toStep,
        array $extra = []
    ): array {
        $logs = [];
        $creator = Auth::user()->login;
        $now = now();

        foreach ($renewals as $renewal) {
            $logs[] = array_merge([
                'task_id' => $renewal->id,
                'job_id' => $jobId,
                'from_step' => $renewal->step,
                'to_step' => $toStep,
                'creator' => $creator,
                'created_at' => $now,
            ], $extra);
        }

        return $logs;
    }

    /**
     * Build log entries for closing renewals with invoice step changes.
     *
     * @param Collection $renewals The renewals being closed
     * @param int $jobId The job ID for grouping
     * @param int $toStep The target step
     * @param int $toInvoiceStep The target invoice step
     * @return array The log entries ready for batch insert
     */
    public function buildClosingLogs(
        Collection $renewals,
        int $jobId,
        int $toStep,
        int $toInvoiceStep
    ): array {
        $logs = [];
        $creator = Auth::user()->login;
        $now = now();

        foreach ($renewals as $renewal) {
            $logs[] = [
                'task_id' => $renewal->id,
                'job_id' => $jobId,
                'from_step' => $renewal->step,
                'to_step' => $toStep,
                'from_invoice' => $renewal->invoice_step ?? 0,
                'to_invoice' => $toInvoiceStep,
                'creator' => $creator,
                'created_at' => $now,
            ];
        }

        return $logs;
    }
}
