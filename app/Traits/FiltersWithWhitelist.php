<?php

namespace App\Traits;

/**
 * Provides common filter validation methods for filter services.
 *
 * This trait standardizes filter validation patterns across services that
 * use whitelist-based filtering for security.
 *
 * Usage:
 * 1. Define ALLOWED_FILTER_KEYS constant in your service
 * 2. Use this trait
 * 3. Call shouldSkipFilter() or isValidFilterKey() in your filter loop
 */
trait FiltersWithWhitelist
{
    /**
     * Check if a filter value should be skipped (null, empty, or whitespace-only).
     *
     * @param  mixed  $value  The filter value to check
     * @return bool True if the value should be skipped
     */
    protected function shouldSkipFilter(mixed $value): bool
    {
        return $value === '' || $value === null || (is_string($value) && trim($value) === '');
    }

    /**
     * Check if a filter key is in the allowed list.
     *
     * @param  string  $key  The filter key to validate
     * @return bool True if the key is allowed
     */
    public function isValidFilterKey(string $key): bool
    {
        return in_array($key, static::ALLOWED_FILTER_KEYS, true);
    }

    /**
     * Check if a filter should be applied (has value and is in whitelist).
     *
     * @param  string  $key  The filter key
     * @param  mixed  $value  The filter value
     * @return bool True if the filter should be applied
     */
    protected function shouldApplyFilter(string $key, mixed $value): bool
    {
        return ! $this->shouldSkipFilter($value) && $this->isValidFilterKey($key);
    }
}
