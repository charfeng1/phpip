<?php

namespace App\Repositories;

use App\Enums\ActorRole;
use App\Enums\ClassifierType;
use App\Enums\EventCode;
use App\Models\Matter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Repository for Task-related database queries.
 *
 * Centralizes complex renewal queries that were previously in the Task model
 * and RenewalController. Provides a testable, injectable interface for
 * querying renewal tasks with filtering capabilities.
 *
 * Phase 4 refactoring: Extracted from Task::renewals() and RenewalController filtering.
 */
class TaskRepository
{
    /**
     * Build a comprehensive query for renewal tasks with fees and matter details.
     *
     * This complex query joins tasks with their matters, events, actors, and fees to provide
     * all information needed for renewal management and invoicing. Includes:
     * - Task and matter details
     * - Applicant/owner information with small entity status
     * - Client information for billing
     * - Fee calculations with various discount scenarios
     * - Filing, grant, and publication dates
     * - Multi-language country names and titles
     *
     * @param array $filters Optional filters to apply (Title, Case, Qt, Fromdate, etc.)
     * @return Builder Query builder for renewal tasks
     */
    public function renewals(array $filters = []): Builder
    {
        $query = $this->buildBaseRenewalQuery();

        if (! empty($filters)) {
            $query = $this->applyRenewalFilters($query, $filters);
        }

        return $query;
    }

    /**
     * Get renewals for specific task IDs.
     *
     * @param array $taskIds Array of task IDs to retrieve
     * @return Builder
     */
    public function renewalsByIds(array $taskIds): Builder
    {
        return $this->buildBaseRenewalQuery()
            ->whereIn('task.id', $taskIds);
    }

    /**
     * Get renewals ready for export (invoice_step = 1).
     *
     * @return Builder
     */
    public function renewalsForExport(): Builder
    {
        return $this->buildBaseRenewalQuery()
            ->where('invoice_step', 1)
            ->orderBy('pmal_cli.actor_id');
    }

    /**
     * Build the base renewal query without filters.
     *
     * Extracted from Task::renewals() for reusability.
     *
     * @return Builder
     */
    protected function buildBaseRenewalQuery(): Builder
    {
        // Database-agnostic implementation supporting both MySQL and PostgreSQL
        $driver = DB::connection()->getDriverName();
        $isPostgres = $driver === 'pgsql';

        // JSON extraction for task detail
        $detailExpr = $isPostgres
            ? "task.detail ->> 'en' AS detail"
            : "JSON_UNQUOTE(JSON_EXTRACT(task.detail, '$.\"en\"')) AS detail";

        // JSON extraction for country names
        $countryFR = $isPostgres ? "mcountry.name ->> 'fr'" : 'JSON_UNQUOTE(JSON_EXTRACT(mcountry.name, "$.fr"))';
        $countryEN = $isPostgres ? "mcountry.name ->> 'en'" : 'JSON_UNQUOTE(JSON_EXTRACT(mcountry.name, "$.en"))';
        $countryDE = $isPostgres ? "mcountry.name ->> 'de'" : 'JSON_UNQUOTE(JSON_EXTRACT(mcountry.name, "$.de"))';

        // GROUP_CONCAT / STRING_AGG for applicant names
        $applicantNameExpr = $this->buildApplicantNameExpression($isPostgres);

        // COALESCE for container_id
        $containerOrMatter = 'COALESCE(matter.container_id, matter.id)';

        // Fee join condition with JSON extraction
        $feeDetailExpr = $isPostgres
            ? "(task.detail ->> 'en')::INTEGER"
            : "CAST(JSON_UNQUOTE(JSON_EXTRACT(task.detail, '$.\"en\"')) AS UNSIGNED)";

        // Small entity calculation with database-agnostic casting
        $smallEntityExpr = $isPostgres
            ? 'COALESCE(MIN(own.small_entity::int), MIN(ownc.small_entity::int), MIN(appl.small_entity::int), MIN(applc.small_entity::int))::boolean AS small_entity'
            : 'COALESCE(MIN(CAST(own.small_entity AS UNSIGNED)), MIN(CAST(ownc.small_entity AS UNSIGNED)), MIN(CAST(appl.small_entity AS UNSIGNED)), MIN(CAST(applc.small_entity AS UNSIGNED))) AS small_entity';

        return Matter::select([
            'task.id',
            DB::raw($detailExpr),
            'task.due_date',
            'task.done',
            'task.done_date',
            'event.matter_id',
            DB::raw('COALESCE(MAX(fees.cost), task.cost) AS cost'),
            DB::raw('COALESCE(MAX(fees.fee), task.fee) AS fee'),
            DB::raw('COALESCE(MAX(fees.cost_reduced), MAX(fees.cost), task.cost) AS cost_reduced'),
            DB::raw('COALESCE(MAX(fees.fee_reduced), MAX(fees.fee), task.fee) AS fee_reduced'),
            DB::raw('COALESCE(MAX(fees.cost_sup), MAX(fees.cost), task.cost) AS cost_sup'),
            DB::raw('COALESCE(MAX(fees.fee_sup), MAX(fees.fee), task.fee) AS fee_sup'),
            DB::raw('COALESCE(MAX(fees.cost_sup_reduced), MAX(fees.cost), task.cost) AS cost_sup_reduced'),
            DB::raw('COALESCE(MAX(fees.fee_sup_reduced), MAX(fees.fee), task.fee) AS fee_sup_reduced'),
            'task.trigger_id',
            'matter.category_code AS category',
            'matter.caseref',
            'matter.uid',
            'matter.country',
            DB::raw("{$countryFR} AS country_FR"),
            DB::raw("{$countryEN} AS country_EN"),
            DB::raw("{$countryDE} AS country_DE"),
            'matter.origin',
            DB::raw($smallEntityExpr),
            'fil.event_date AS fil_date',
            'fil.detail AS fil_num',
            'grt.event_date AS grt_date',
            'event.code AS event_name',
            'event.event_date',
            'event.detail AS number',
            DB::raw($applicantNameExpr),
            DB::raw('COALESCE(pa_cli.name, clic.name) AS client_name'),
            DB::raw('COALESCE(pa_cli.address, clic.address) AS client_address'),
            DB::raw('COALESCE(pa_cli.country, clic.country) AS client_country'),
            DB::raw('COALESCE(pa_cli.ren_discount, clic.ren_discount) AS discount'),
            DB::raw('COALESCE(pmal_cli.actor_id, cliclnk.actor_id) AS client_id'),
            DB::raw('COALESCE(pmal_cli.actor_ref, cliclnk.actor_ref) AS client_ref'),
            DB::raw('COALESCE(pa_cli.email, clic.email) AS email'),
            DB::raw('COALESCE(pa_cli.language, clic.language) AS language'),
            'matter.responsible',
            'tit.value AS short_title',
            'titof.value AS title',
            'pub.detail AS pub_num',
            'task.step',
            'task.grace_period',
            'task.invoice_step',
            'matter.expire_date',
            DB::raw('MAX(fees.fee) AS table_fee'),
        ])
            ->join('event', 'matter.id', 'event.matter_id')
            ->join('task', 'task.trigger_id', 'event.id')
            ->leftJoin('country as mcountry', 'mcountry.iso', 'matter.country')
            // Events
            ->leftJoin('event AS fil', fn ($join) => $join->on('matter.id', 'fil.matter_id')
                ->where('fil.code', EventCode::FILING->value))
            ->leftJoin('event AS pub', fn ($join) => $join->on('matter.id', 'pub.matter_id')
                ->where('pub.code', EventCode::PUBLICATION->value))
            ->leftJoin('event AS grt', fn ($join) => $join->on('matter.id', 'grt.matter_id')
                ->where('grt.code', EventCode::GRANT->value))
            // Applicants and owners
            ->leftJoin(DB::raw("matter_actor_lnk lappl JOIN actor appl ON appl.id = lappl.actor_id AND lappl.role = '".ActorRole::APPLICANT->value."'"),
                'matter.id', 'lappl.matter_id')
            ->leftJoin(DB::raw("matter_actor_lnk lapplc JOIN actor applc ON applc.id = lapplc.actor_id AND lapplc.role = '".ActorRole::APPLICANT->value."' AND lapplc.shared = true"),
                'matter.container_id', 'lapplc.matter_id')
            ->leftJoin(DB::raw("matter_actor_lnk lown JOIN actor own ON own.id = lown.actor_id AND lown.role = '".ActorRole::OWNER->value."'"),
                'matter.id', 'lown.matter_id')
            ->leftJoin(DB::raw("matter_actor_lnk lownc JOIN actor ownc ON ownc.id = lownc.actor_id AND lownc.role = '".ActorRole::OWNER->value."' AND lownc.shared = true"),
                'matter.container_id', 'lownc.matter_id')
            // Clients
            ->leftJoin(DB::raw('matter_actor_lnk pmal_cli JOIN actor pa_cli ON pa_cli.id = pmal_cli.actor_id'),
                fn ($join) => $join->on('matter.id', 'pmal_cli.matter_id')->where('pmal_cli.role', ActorRole::CLIENT->value))
            ->leftJoin(DB::raw('matter_actor_lnk cliclnk JOIN actor clic ON clic.id = cliclnk.actor_id'),
                fn ($join) => $join->on('matter.container_id', 'cliclnk.matter_id')
                    ->where([['cliclnk.role', ActorRole::CLIENT->value], ['cliclnk.shared', true]]))
            // Titles
            ->leftJoin('classifier AS tit', fn ($join) => $join->on(DB::raw($containerOrMatter), 'tit.matter_id')
                ->where('tit.type_code', ClassifierType::TITLE->value))
            ->leftJoin('classifier AS titof', fn ($join) => $join->on(DB::raw($containerOrMatter), 'titof.matter_id')
                ->where('titof.type_code', ClassifierType::TITLE_OFFICIAL->value))
            // Fees
            ->leftJoin('fees', function ($join) use ($feeDetailExpr) {
                $join->on('fees.for_country', 'matter.country')
                    ->on('fees.for_category', 'matter.category_code')
                    ->on(DB::raw($feeDetailExpr), 'fees.qt');
            })
            ->where('task.code', EventCode::RENEWAL->value)
            ->groupBy([
                'task.id',
                'task.due_date',
                'task.done',
                'task.done_date',
                'task.trigger_id',
                'task.cost',
                'task.fee',
                'task.step',
                'task.grace_period',
                'task.invoice_step',
                'task.detail',
                'event.matter_id',
                'event.code',
                'event.event_date',
                'event.detail',
                'matter.category_code',
                'matter.caseref',
                'matter.uid',
                'matter.country',
                'matter.origin',
                'matter.responsible',
                'matter.expire_date',
                'matter.container_id',
                'mcountry.name',
                'mcountry.name_fr',
                'mcountry.name_de',
                'fil.event_date',
                'fil.detail',
                'pub.detail',
                'grt.event_date',
                'tit.value',
                'titof.value',
                'pa_cli.name',
                'pa_cli.address',
                'pa_cli.country',
                'pa_cli.ren_discount',
                'pa_cli.email',
                'pa_cli.language',
                'clic.name',
                'clic.address',
                'clic.country',
                'clic.ren_discount',
                'clic.email',
                'clic.language',
                'pmal_cli.actor_id',
                'pmal_cli.actor_ref',
                'cliclnk.actor_id',
                'cliclnk.actor_ref',
            ]);
    }

    /**
     * Build the applicant name aggregation expression.
     *
     * @param bool $isPostgres Whether using PostgreSQL
     * @return string SQL expression for applicant name aggregation
     */
    protected function buildApplicantNameExpression(bool $isPostgres): string
    {
        if ($isPostgres) {
            return "CASE
                WHEN STRING_AGG(DISTINCT ownc.name, '; ') IS NOT NULL OR STRING_AGG(DISTINCT own.name, '; ') IS NOT NULL
                THEN CONCAT_WS('; ', STRING_AGG(DISTINCT ownc.name, '; '), STRING_AGG(DISTINCT own.name, '; '))
                ELSE CONCAT_WS('; ', STRING_AGG(DISTINCT applc.name, '; '), STRING_AGG(DISTINCT appl.name, '; '))
            END AS applicant_name";
        }

        return "IF(GROUP_CONCAT(DISTINCT ownc.name) IS NOT NULL OR GROUP_CONCAT(DISTINCT own.name) IS NOT NULL,
            CONCAT_WS('; ', GROUP_CONCAT(DISTINCT ownc.name SEPARATOR '; '), GROUP_CONCAT(DISTINCT own.name SEPARATOR '; ')),
            CONCAT_WS('; ', GROUP_CONCAT(DISTINCT applc.name SEPARATOR '; '), GROUP_CONCAT(DISTINCT appl.name SEPARATOR '; '))
        ) AS applicant_name";
    }

    /**
     * Whitelist of allowed renewal filter keys to prevent SQL injection.
     * Only these keys can be used in filter queries.
     */
    protected const ALLOWED_RENEWAL_FILTER_KEYS = [
        'Title', 'Case', 'Qt', 'Fromdate', 'Untildate', 'Name', 'Country',
        'grace', 'step', 'invoice_step', 'my_renewals', 'dead',
    ];

    /**
     * Apply filters to a renewal query.
     *
     * Extracted from RenewalController::index() for reusability and testability.
     *
     * @param Builder $query The base query to filter
     * @param array $filters Associative array of filter key => value pairs
     * @return Builder The filtered query
     */
    public function applyRenewalFilters(Builder $query, array $filters): Builder
    {
        foreach ($filters as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }

            // Skip unknown filter keys to prevent SQL injection
            if (! in_array($key, self::ALLOWED_RENEWAL_FILTER_KEYS, true)) {
                continue;
            }

            $query = match ($key) {
                'Title' => $query->whereLike('tit.value', "%{$value}%"),
                'Case' => $query->whereLike('caseref', "{$value}%"),
                'Qt' => $query->where('task.detail->en', $value),
                'Fromdate' => $query->where('due_date', '>=', $value),
                'Untildate' => $query->where('due_date', '<=', $value),
                'Name' => $query->where(function ($q) use ($value) {
                    $like = $value.'%';
                    $q->where('pa_cli.name', 'LIKE', $like)
                        ->orWhere('clic.name', 'LIKE', $like);
                }),
                'Country' => $query->whereLike('matter.country', "{$value}%"),
                'grace' => $query->where('grace_period', $value),
                'step' => $query->where('step', $value),
                'invoice_step' => $query->where('invoice_step', $value),
                'my_renewals' => $value && Auth::check() ? $query->where('task.assigned_to', Auth::user()->login) : $query,
                'dead' => $value == 0 ? $query->where('matter.dead', 0) : $query,
                default => $query, // Unreachable due to whitelist check, but required for match exhaustiveness
            };
        }

        return $query;
    }

    /**
     * Determine if results should show only pending renewals.
     *
     * Based on step and invoice_step filters, determines if we're at the
     * beginning of the pipeline (show only pending) or viewing a specific step.
     *
     * @param array $filters The applied filters
     * @return bool True if should show only pending (done = 0)
     */
    public function shouldShowOnlyPending(array $filters): bool
    {
        $step = $filters['step'] ?? null;
        $invoiceStep = $filters['invoice_step'] ?? null;

        $withStep = $step !== null && $step !== '' && $step != 0;
        $withInvoice = $invoiceStep !== null && $invoiceStep !== '' && $invoiceStep != 0;

        return ! ($withStep || $withInvoice);
    }

    /**
     * Determine sort order based on workflow step.
     *
     * For closed renewals (step 10) or paid invoices (invoice_step 3),
     * sort by most recent first.
     *
     * @param int|null $step Current step filter
     * @param int|null $invoiceStep Current invoice step filter
     * @return string|null 'desc' for descending, null for default order
     */
    public function getSortDirection(?int $step, ?int $invoiceStep): ?string
    {
        if ($step == 10 || $invoiceStep == 3) {
            return 'desc';
        }

        return null;
    }
}
