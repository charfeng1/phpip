<?php

namespace App\Services;

use App\Models\RenewalsLog;
use Illuminate\Database\Eloquent\Builder;

/**
 * Service for filtering renewal logs.
 *
 * Handles filtering of renewal processing logs by:
 * - Matter reference (uid)
 * - Client display name
 * - Job ID
 * - User (creator name)
 * - Date range (Fromdate, Untildate)
 */
class RenewalLogFilterService
{
    /**
     * Allowed filter keys for security (whitelist approach).
     */
    private const ALLOWED_FILTER_KEYS = [
        'Matter',
        'Client',
        'Job',
        'User',
        'Fromdate',
        'Untildate',
    ];

    /**
     * Filter renewal logs based on provided criteria.
     *
     * @param  mixed  $query  The query builder to filter (RenewalsLog or Builder)
     * @param  array  $filters  Filter key-value pairs
     * @return mixed The filtered query builder
     */
    public function filterLogs(mixed $query, array $filters): mixed
    {
        foreach ($filters as $key => $value) {
            // Skip null, empty, or whitespace-only values
            if ($value === '' || $value === null || (is_string($value) && trim($value) === '')) {
                continue;
            }

            // Skip unknown filter keys for security
            if (! in_array($key, self::ALLOWED_FILTER_KEYS, true)) {
                continue;
            }

            $query = $this->applyFilter($query, $key, $value);
        }

        return $query;
    }

    /**
     * Apply a single filter to the query.
     *
     * @param  mixed  $query  The query builder
     * @param  string  $key  The filter key
     * @param  mixed  $value  The filter value
     * @return mixed The filtered query builder
     */
    protected function applyFilter(mixed $query, string $key, mixed $value): mixed
    {
        return match ($key) {
            'Matter' => $query->whereHas('task', fn ($q) => $q->whereHas('matter', fn ($q2) => $q2->whereLike('uid', "{$value}%"))),
            'Client' => $query->whereHas('task', fn ($q) => $q->whereHas('matter', fn ($q2) => $q2->whereHas('client', fn ($q3) => $q3->whereLike('display_name', "{$value}%")))),
            'Job' => $query->where('job_id', $value),
            'User' => $query->whereHas('creatorInfo', fn ($q) => $q->whereLike('name', "{$value}%")),
            'Fromdate' => $query->where('created_at', '>=', $value),
            'Untildate' => $query->where('created_at', '<=', $value),
            default => $query, // Unreachable due to whitelist check, but required for match exhaustiveness
        };
    }

    /**
     * Validate if a filter key is allowed.
     *
     * @param  string  $key  The filter key to validate
     * @return bool True if filter is allowed, false otherwise
     */
    public function isValidFilterKey(string $key): bool
    {
        return in_array($key, self::ALLOWED_FILTER_KEYS, true);
    }
}
