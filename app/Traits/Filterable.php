<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

/**
 * Trait for applying filters to Eloquent query builders.
 *
 * Controllers can define filter rules and this trait will apply them
 * based on request parameters.
 *
 * Usage:
 * 1. Use this trait in your controller
 * 2. Define $filterRules array mapping parameter names to filter callbacks
 * 3. Call $this->applyFilters($query, $request) in your index method
 *
 * Example:
 * protected array $filterRules = [
 *     'Code' => fn($q, $v) => $q->whereLike('code', "$v%"),
 *     'Name' => fn($q, $v) => $q->whereJsonLike('name', $v),
 * ];
 */
trait Filterable
{
    /**
     * Filter rules mapping parameter names to filter callbacks.
     *
     * @var array<string, callable>
     */
    protected array $filterRules = [];

    /**
     * Apply filters from request to the query builder.
     *
     * @param  Builder  $query  The Eloquent query builder
     * @param  Request  $request  The HTTP request containing filter parameters
     * @return Builder The modified query builder
     */
    protected function applyFilters(Builder $query, Request $request): Builder
    {
        foreach ($this->filterRules as $key => $callback) {
            // Use filled() to properly handle zero values (0, "0")
            if ($request->filled($key)) {
                $query = $callback($query, $request->input($key));
            }
        }

        return $query;
    }

    /**
     * Apply filters and return paginated results with filters appended.
     *
     * @param  Builder  $query  The Eloquent query builder
     * @param  Request  $request  The HTTP request
     * @param  int|null  $perPage  Items per page (null uses config default)
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    protected function filterAndPaginate(Builder $query, Request $request, ?int $perPage = null)
    {
        $perPage = $perPage ?? config('pagination.default', 21);

        $this->applyFilters($query, $request);

        $results = $query->paginate($perPage);
        $results->appends($request->input())->links();

        return $results;
    }

    /**
     * Apply filters and return simple paginated results.
     *
     * @param  Builder  $query  The Eloquent query builder
     * @param  Request  $request  The HTTP request
     * @param  int|null  $perPage  Items per page (null uses config default)
     * @return \Illuminate\Contracts\Pagination\Paginator
     */
    protected function filterAndSimplePaginate(Builder $query, Request $request, ?int $perPage = null)
    {
        $perPage = $perPage ?? config('pagination.default', 21);

        $this->applyFilters($query, $request);

        $results = $query->simplePaginate($perPage);
        $results->appends($request->input())->links();

        return $results;
    }

    /**
     * Get old filter values for view (useful for repopulating filter forms).
     *
     * @param  Request  $request
     * @return array
     */
    protected function getOldFilters(Request $request): array
    {
        $oldFilters = [];

        foreach (array_keys($this->filterRules) as $key) {
            // Use filled() to properly handle zero values (0, "0")
            if ($request->filled($key)) {
                $oldFilters[$key] = $request->input($key);
            }
        }

        return $oldFilters;
    }
}
