<?php

namespace App\Repositories;

use App\Enums\ActorRole;
use App\Enums\EventCode;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Matter;
use App\Services\TeamService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Repository for Matter-related database queries.
 *
 * Centralizes complex matter filtering queries that were previously in the Matter model.
 * Provides a testable, injectable interface for querying matters with filtering capabilities.
 *
 * Phase 4 refactoring: Extracted from Matter::filter() method.
 */
class MatterRepository
{
    /**
     * Filter matters with comprehensive filtering, sorting, and role-based access control.
     *
     * This complex query joins matters with actors, events, and classifiers to provide
     * all information needed for the matter list view. Includes:
     * - Client, agent, applicant information
     * - Filing, publication, grant dates and numbers
     * - Title classifiers
     * - Status events
     * - Role-based access control
     *
     * @param string $sortkey Column to sort by (default: 'id')
     * @param string $sortdir Sort direction 'asc' or 'desc' (default: 'desc')
     * @param array $filters Filter key-value pairs
     * @param string|bool $displayWith Filter by category display_with value
     * @param bool $includeDead Whether to include dead families
     * @return Builder Query builder for filtered matters
     */
    public function filter(
        string $sortkey = 'id',
        string $sortdir = 'desc',
        array $filters = [],
        string|bool $displayWith = false,
        bool $includeDead = false
    ): Builder {
        $query = $this->buildBaseFilterQuery();

        // Handle inventor join conditionally
        $query = $this->addInventorJoin($query, $filters);

        // Apply role-based access control
        $query = $this->applyAccessControl($query);

        // Apply display_with filter
        if ($displayWith) {
            $query->where('matter_category.display_with', $displayWith);
        }

        // Apply filters
        if (! empty($filters)) {
            $query = $this->applyFilters($query, $filters, $sortkey, $sortdir);
        }

        // Handle dead families
        if (! $includeDead) {
            $query->whereRaw('(select count(1) from matter m where m.caseref = matter.caseref and m.dead = false) > 0');
        }

        // Apply sorting and grouping
        $query = $this->applySortingAndGrouping($query, $sortkey, $sortdir);

        return $query;
    }

    /**
     * Get category matter counts for dashboard.
     *
     * @param int|null $whatTasks Filter type (1 = my tasks, >1 = client tasks)
     * @return \Illuminate\Support\Collection
     */
    public function getCategoryMatterCount(?int $whatTasks = null): \Illuminate\Support\Collection
    {
        $authUserId = Auth::id();
        $authUser = Auth::user();
        $authUserRole = $authUser?->default_role;
        $authUserLogin = $authUser?->login;
        // Only treat as client if explicitly has CLIENT role (not when unauthenticated/no role)
        $isClient = $authUserRole === UserRole::CLIENT->value;

        return Category::withCount(['matters as total' => function ($query) use ($whatTasks, $authUserLogin, $authUserId, $isClient) {
            // Only filter by responsible if we have a valid login
            if ($whatTasks == 1 && $authUserLogin !== null) {
                $query->where('responsible', $authUserLogin);
            }
            if ($whatTasks > 1) {
                $query->whereHas('client', function ($aq) use ($whatTasks) {
                    $aq->where('actor_id', $whatTasks);
                });
            }
            // Only apply client filter if user is authenticated and has client role
            if ($isClient && $authUserId !== null) {
                $query->whereHas('client', function ($aq) use ($authUserId) {
                    $aq->where('actor_id', $authUserId);
                });
            }
        }])
            ->when($isClient && $authUserId !== null, function ($query) use ($authUserId) {
                $query->whereHas('matters', function ($q) use ($authUserId) {
                    $q->whereHas('client', function ($aq) use ($authUserId) {
                        $aq->where('actor_id', $authUserId);
                    });
                });
            })
            ->get();
    }

    /**
     * Find a matter by ID with common eager loads.
     *
     * @param int $id Matter ID
     * @return Matter|null
     */
    public function findWithRelations(int $id): ?Matter
    {
        return Matter::with([
            'tasksPending.info',
            'renewalsPending',
            'events.info',
            'titles',
            'actors',
            'classifiers',
            'family',
            'priorityTo',
            'linkedBy',
        ])->find($id);
    }

    /**
     * Get external matters claiming priority to any of the given family IDs.
     *
     * @param \Illuminate\Support\Collection $familyIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getExternalPriorityMatters(\Illuminate\Support\Collection $familyIds): \Illuminate\Database\Eloquent\Collection
    {
        return Matter::whereHas('events', function ($q) use ($familyIds) {
            $q->where('code', EventCode::PRIORITY->value)
                ->whereIn('alt_matter_id', $familyIds);
        })->get();
    }

    /**
     * Find a matter by ID.
     *
     * @param int $id Matter ID
     * @return Matter|null
     */
    public function find(int $id): ?Matter
    {
        return Matter::find($id);
    }

    /**
     * Build the base filter query with all joins.
     *
     * @return Builder
     */
    protected function buildBaseFilterQuery(): Builder
    {
        $locale = app()->getLocale();
        $baseLocale = preg_replace('/[^a-zA-Z]/', '', substr($locale, 0, 2));

        $driver = DB::connection()->getDriverName();
        $isPostgres = $driver === 'pgsql';

        // Build aggregation expressions
        $expressions = $this->buildAggregationExpressions($isPostgres, $baseLocale);

        return Matter::select(
            'matter.uid AS Ref',
            'matter.country AS country',
            'matter.category_code AS Cat',
            'matter.origin',
            DB::raw($expressions['status']),
            DB::raw('MIN(status.event_date) AS Status_date'),
            DB::raw($expressions['client']),
            DB::raw($expressions['clRef']),
            DB::raw($expressions['applicant']),
            DB::raw($expressions['agent']),
            DB::raw($expressions['agtRef']),
            'tit1.value AS Title',
            DB::raw('COALESCE(tit2.value, tit1.value) AS Title2'),
            'tit3.value AS Title3',
            DB::raw("CONCAT_WS(' ', inv.name, inv.first_name) as Inventor1"),
            'fil.event_date AS Filed',
            'fil.detail AS FilNo',
            'pub.event_date AS Published',
            'pub.detail AS PubNo',
            DB::raw('COALESCE(grt.event_date, reg.event_date) AS Granted'),
            DB::raw('COALESCE(grt.detail, reg.detail) AS GrtNo'),
            'matter.id',
            'matter.container_id',
            'matter.parent_id',
            'matter.type_code',
            'matter.responsible',
            'del.login AS delegate',
            'matter.dead',
            DB::raw('CASE WHEN matter.container_id IS NULL THEN 1 ELSE 0 END AS Ctnr'),
            'matter.alt_ref AS Alt_Ref'
        )
            ->join('matter_category', 'matter.category_code', 'matter_category.code')
            // Client joins
            ->leftJoin(
                DB::raw('matter_actor_lnk clilnk JOIN actor cli ON cli.id = clilnk.actor_id'),
                fn ($join) => $join->on('matter.id', 'clilnk.matter_id')
                    ->where('clilnk.role', ActorRole::CLIENT->value)
            )
            ->leftJoin(
                DB::raw('matter_actor_lnk cliclnk JOIN actor clic ON clic.id = cliclnk.actor_id'),
                fn ($join) => $join->on('matter.container_id', 'cliclnk.matter_id')
                    ->where([['cliclnk.role', ActorRole::CLIENT->value], ['cliclnk.shared', true]])
            )
            // Agent joins
            ->leftJoin(
                DB::raw('matter_actor_lnk agtlnk JOIN actor agt ON agt.id = agtlnk.actor_id'),
                fn ($join) => $join->on('matter.id', 'agtlnk.matter_id')
                    ->where([['agtlnk.role', ActorRole::AGENT->value], ['agtlnk.display_order', 1]])
            )
            ->leftJoin(
                DB::raw('matter_actor_lnk agtclnk JOIN actor agtc ON agtc.id = agtclnk.actor_id'),
                fn ($join) => $join->on('matter.container_id', 'agtclnk.matter_id')
                    ->where([['agtclnk.role', ActorRole::AGENT->value], ['agtclnk.shared', 1]])
            )
            // Applicant and delegate joins
            ->leftJoin(
                DB::raw('matter_actor_lnk applnk JOIN actor app ON app.id = applnk.actor_id'),
                fn ($join) => $join->on(DB::raw('COALESCE(matter.container_id, matter.id)'), 'applnk.matter_id')
                    ->where('applnk.role', ActorRole::APPLICANT->value)
            )
            ->leftJoin(
                DB::raw('matter_actor_lnk dellnk JOIN actor del ON del.id = dellnk.actor_id'),
                fn ($join) => $join->on(DB::raw('COALESCE(matter.container_id, matter.id)'), 'dellnk.matter_id')
                    ->where('dellnk.role', ActorRole::DELEGATE->value)
            )
            // Event joins
            ->leftJoin('event AS fil', fn ($join) => $join->on('matter.id', 'fil.matter_id')
                ->where('fil.code', EventCode::FILING->value))
            ->leftJoin('event AS pub', fn ($join) => $join->on('matter.id', 'pub.matter_id')
                ->where('pub.code', EventCode::PUBLICATION->value))
            ->leftJoin('event AS grt', fn ($join) => $join->on('matter.id', 'grt.matter_id')
                ->where('grt.code', EventCode::GRANT->value))
            ->leftJoin('event AS reg', fn ($join) => $join->on('matter.id', 'reg.matter_id')
                ->where('reg.code', EventCode::REGISTRATION->value))
            // Status event joins
            ->leftJoin(
                DB::raw('event status JOIN event_name ON event_name.code = status.code AND event_name.status_event = true'),
                'matter.id',
                'status.matter_id'
            )
            ->leftJoin(
                DB::raw('event e2 JOIN event_name en2 ON e2.code = en2.code AND en2.status_event = true'),
                fn ($join) => $join->on('status.matter_id', 'e2.matter_id')
                    ->whereColumn('status.event_date', '<', 'e2.event_date')
            )
            // Title classifier joins
            ->leftJoin(
                DB::raw('classifier tit1 JOIN classifier_type ct1 ON tit1.type_code = ct1.code AND ct1.main_display = true AND ct1.display_order = 1'),
                DB::raw('COALESCE(matter.container_id, matter.id)'),
                'tit1.matter_id'
            )
            ->leftJoin(
                DB::raw('classifier tit2 JOIN classifier_type ct2 ON tit2.type_code = ct2.code AND ct2.main_display = true AND ct2.display_order = 2'),
                DB::raw('COALESCE(matter.container_id, matter.id)'),
                'tit2.matter_id'
            )
            ->leftJoin(
                DB::raw('classifier tit3 JOIN classifier_type ct3 ON tit3.type_code = ct3.code AND ct3.main_display = true AND ct3.display_order = 3'),
                DB::raw('COALESCE(matter.container_id, matter.id)'),
                'tit3.matter_id'
            )
            ->where('e2.matter_id', null);
    }

    /**
     * Build aggregation expressions for MySQL/PostgreSQL.
     *
     * @param bool $isPostgres
     * @param string $baseLocale
     * @return array
     */
    protected function buildAggregationExpressions(bool $isPostgres, string $baseLocale): array
    {
        if ($isPostgres) {
            return [
                'status' => "STRING_AGG(DISTINCT event_name.name ->> '{$baseLocale}', '|') AS Status",
                'client' => "STRING_AGG(DISTINCT COALESCE(cli.display_name, clic.display_name, cli.name, clic.name), '; ') AS Client",
                'clRef' => "STRING_AGG(DISTINCT COALESCE(clilnk.actor_ref, cliclnk.actor_ref), '; ') AS ClRef",
                'applicant' => "STRING_AGG(DISTINCT COALESCE(app.display_name, app.name), '; ') AS Applicant",
                'agent' => "STRING_AGG(DISTINCT COALESCE(agt.display_name, agtc.display_name, agt.name, agtc.name), '; ') AS AgentName",
                'agtRef' => "STRING_AGG(DISTINCT COALESCE(agtlnk.actor_ref, agtclnk.actor_ref), '; ') AS AgtRef",
            ];
        }

        return [
            'status' => "GROUP_CONCAT(DISTINCT JSON_UNQUOTE(JSON_EXTRACT(event_name.name, '$.\"$baseLocale\"')) SEPARATOR '|') AS Status",
            'client' => "GROUP_CONCAT(DISTINCT COALESCE(cli.display_name, clic.display_name, cli.name, clic.name) SEPARATOR '; ') AS Client",
            'clRef' => "GROUP_CONCAT(DISTINCT COALESCE(clilnk.actor_ref, cliclnk.actor_ref) SEPARATOR '; ') AS ClRef",
            'applicant' => "GROUP_CONCAT(DISTINCT COALESCE(app.display_name, app.name) SEPARATOR '; ') AS Applicant",
            'agent' => "GROUP_CONCAT(DISTINCT COALESCE(agt.display_name, agtc.display_name, agt.name, agtc.name) SEPARATOR '; ') AS AgentName",
            'agtRef' => "GROUP_CONCAT(DISTINCT COALESCE(agtlnk.actor_ref, agtclnk.actor_ref) SEPARATOR '; ') AS AgtRef",
        ];
    }

    /**
     * Add inventor join conditionally based on filters.
     *
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    protected function addInventorJoin(Builder $query, array $filters): Builder
    {
        if (array_key_exists('Inventor1', $filters)) {
            return $query->leftJoin(
                DB::raw('matter_actor_lnk invlnk JOIN actor inv ON inv.id = invlnk.actor_id'),
                fn ($join) => $join->on(DB::raw('COALESCE(matter.container_id, matter.id)'), 'invlnk.matter_id')
                    ->where('invlnk.role', ActorRole::INVENTOR->value)
            );
        }

        return $query->leftJoin(
            DB::raw('matter_actor_lnk invlnk JOIN actor inv ON inv.id = invlnk.actor_id'),
            fn ($join) => $join->on(DB::raw('COALESCE(matter.container_id, matter.id)'), 'invlnk.matter_id')
                ->where([['invlnk.role', ActorRole::INVENTOR->value], ['invlnk.display_order', 1]])
        );
    }

    /**
     * Apply role-based access control.
     *
     * @param Builder $query
     * @return Builder
     */
    protected function applyAccessControl(Builder $query): Builder
    {
        $authUser = Auth::user();
        if (! $authUser) {
            // No authenticated user - return query unchanged (controller should handle auth)
            return $query;
        }

        $authUserRole = $authUser->default_role;
        $authUserId = $authUser->id;

        // Only restrict access for users with CLIENT role
        if ($authUserRole === UserRole::CLIENT->value) {
            $query->where(function ($q) use ($authUserId) {
                $q->where('cli.id', $authUserId)
                    ->orWhere('clic.id', $authUserId);
            });
        }

        return $query;
    }

    /**
     * Whitelist of allowed filter keys to prevent SQL injection.
     * Only these keys can be used in filter queries.
     */
    protected const ALLOWED_FILTER_KEYS = [
        'Ref', 'Cat', 'country', 'Status', 'Status_date', 'Client', 'ClRef',
        'Applicant', 'Agent', 'AgentName', 'AgtRef', 'Title', 'Inventor1',
        'Filed', 'FilNo', 'Published', 'PubNo', 'Granted', 'GrtNo',
        'responsible', 'team', 'Ctnr', 'origin', 'type_code', 'dead',
    ];

    /**
     * Apply filters to the query.
     *
     * @param Builder $query
     * @param array $filters
     * @param string &$sortkey Reference to sortkey (may be modified)
     * @param string &$sortdir Reference to sortdir (may be modified)
     * @return Builder
     */
    protected function applyFilters(Builder $query, array $filters, string &$sortkey, string &$sortdir): Builder
    {
        // When filters are set, default to sorting by caseref ascending
        if ($sortkey == 'id') {
            $sortkey = 'caseref';
            $sortdir = 'asc';
        }

        foreach ($filters as $key => $value) {
            if ($value === '' || $value === null) {
                continue;
            }

            // Skip unknown filter keys to prevent SQL injection
            if (! in_array($key, self::ALLOWED_FILTER_KEYS, true)) {
                continue;
            }

            $query = match ($key) {
                'Ref' => $query->where(fn ($q) => $q->whereLike('uid', "$value%")->orWhereLike('alt_ref', "$value%")),
                'Cat' => $query->whereLike('category_code', "$value%"),
                'country' => $query->whereLike('matter.country', "$value%"),
                'Status' => $query->whereJsonLike('event_name.name', $value),
                'Status_date' => $query->whereLike('status.event_date', "$value%"),
                'Client' => $query->where(fn ($q) => $q->where('cli.name', 'LIKE', "$value%")->orWhere('clic.name', 'LIKE', "$value%")),
                'ClRef' => $query->where(fn ($q) => $q->where('clilnk.actor_ref', 'LIKE', "$value%")->orWhere('cliclnk.actor_ref', 'LIKE', "$value%")),
                'Applicant' => $query->whereLike('app.name', "$value%"),
                'Agent', 'AgentName' => $query->where(fn ($q) => $q->where('agt.name', 'LIKE', "$value%")->orWhere('agtc.name', 'LIKE', "$value%")),
                'AgtRef' => $query->where(fn ($q) => $q->where('agtlnk.actor_ref', 'LIKE', "$value%")->orWhere('agtclnk.actor_ref', 'LIKE', "$value%")),
                'Title' => $query->whereRaw("CONCAT_WS(' ', COALESCE(tit1.value, ''), COALESCE(tit2.value, ''), COALESCE(tit3.value, '')) LIKE ?", ["%$value%"]),
                'Inventor1' => $query->whereLike('inv.name', "$value%"),
                'Filed' => $query->whereLike('fil.event_date', "$value%"),
                'FilNo' => $query->whereLike('fil.detail', "$value%"),
                'Published' => $query->whereLike('pub.event_date', "$value%"),
                'PubNo' => $query->whereLike('pub.detail', "$value%"),
                'Granted' => $query->where(fn ($q) => $q->whereLike('grt.event_date', "$value%")->orWhereLike('reg.event_date', "$value%")),
                'GrtNo' => $query->where(fn ($q) => $q->whereLike('grt.detail', "$value%")->orWhereLike('reg.detail', "$value%")),
                'responsible' => $query->where(fn ($q) => $q->where('matter.responsible', $value)->orWhere('del.login', $value)),
                'team' => $this->applyTeamFilter($query, $value),
                'Ctnr' => $value ? $query->whereNull('container_id') : $query,
                'origin' => $query->whereLike('matter.origin', "$value%"),
                'type_code' => $query->whereLike('matter.type_code', "$value%"),
                'dead' => $query->where('matter.dead', (bool) $value),
                default => $query, // Unreachable due to whitelist check, but required for match exhaustiveness
            };
        }

        return $query;
    }

    /**
     * Apply team filter.
     *
     * @param Builder $query
     * @param mixed $value
     * @return Builder
     */
    protected function applyTeamFilter(Builder $query, mixed $value): Builder
    {
        $authUserId = Auth::id();
        if ($value && $authUserId !== null) {
            $teamService = app(TeamService::class);
            $teamLogins = $teamService->getSubordinateLogins($authUserId, true);
            $query->whereIn('matter.responsible', $teamLogins);
        }

        return $query;
    }

    /**
     * Apply sorting and grouping.
     *
     * @param Builder $query
     * @param string $sortkey
     * @param string $sortdir
     * @return Builder
     */
    protected function applySortingAndGrouping(Builder $query, string $sortkey, string $sortdir): Builder
    {
        $baseGroupBy = [
            'matter.id',
            'tit1.value',
            'tit2.value',
            'tit3.value',
            'inv.name',
            'inv.first_name',
            'fil.event_date',
            'fil.detail',
            'pub.event_date',
            'pub.detail',
            'grt.event_date',
            'grt.detail',
            'reg.event_date',
            'reg.detail',
            'del.login',
        ];

        if ($sortkey == 'caseref') {
            $query->groupBy($baseGroupBy);
            $query->orderBy('matter.caseref', $sortdir);
        } else {
            $groupBy = $baseGroupBy;
            if (! in_array($sortkey, $groupBy)) {
                $groupBy[] = $sortkey;
            }
            $query->groupBy($groupBy)->orderBy($sortkey, $sortdir);
        }

        return $query;
    }
}
