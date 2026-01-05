<?php

namespace App\Traits;

/**
 * TrimsCharColumns Trait
 *
 * Provides automatic trimming of PostgreSQL CHAR column values.
 *
 * PostgreSQL CHAR columns are fixed-length and pad values with spaces to fill
 * the defined column width. This trait automatically trims trailing spaces from
 * CHAR columns when retrieving attributes, eliminating the need for individual
 * accessor methods for each CHAR column.
 *
 * Usage:
 * 1. Add the trait to your model: use TrimsCharColumns;
 * 2. Define the $charColumns property with column names to trim:
 *    protected array $charColumns = ['country', 'login', 'language'];
 * 3. The trait will automatically trim these columns when accessed
 *
 * NULL Handling:
 * - NULL values remain NULL
 * - Empty strings remain empty strings
 * - Whitespace-only strings are trimmed to empty strings
 *
 * Example:
 * ```php
 * class Actor extends Model
 * {
 *     use TrimsCharColumns;
 *
 *     protected array $charColumns = [
 *         'default_role',
 *         'login',
 *         'language',
 *         'country',
 *         'nationality',
 *         'country_mailing',
 *         'country_billing',
 *     ];
 * }
 * ```
 *
 * Note: This approach is preferred over converting CHAR to VARCHAR in the database
 * to maintain compatibility with existing MySQL deployments while supporting
 * PostgreSQL/Supabase installations.
 */
trait TrimsCharColumns
{
    /**
     * Override getAttribute to automatically trim CHAR columns.
     *
     * This method intercepts attribute access and applies trimming to columns
     * defined in the $charColumns property. If a column is not in $charColumns,
     * it falls back to the parent getAttribute behavior.
     *
     * @param  string  $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        // Only trim if this is a CHAR column and the value is a string
        if ($this->isCharColumn($key) && is_string($value)) {
            $trimmed = trim($value);

            // Convert whitespace-only strings to empty string
            // NULL values are preserved as NULL
            return $trimmed === '' && $value !== '' ? '' : $trimmed;
        }

        return $value;
    }

    /**
     * Check if a column is defined as a CHAR column that should be trimmed.
     */
    protected function isCharColumn(string $key): bool
    {
        return property_exists($this, 'charColumns')
            && is_array($this->charColumns)
            && in_array($key, $this->charColumns);
    }
}
