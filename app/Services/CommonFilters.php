<?php

namespace App\Services;

use Closure;
use Illuminate\Database\Eloquent\Builder;

/**
 * Common filter callbacks for use with the Filterable trait.
 *
 * Provides reusable, secure filter functions that handle:
 * - SQL wildcard escaping to prevent injection
 * - JSON field searching (for translatable fields)
 * - Relationship filtering
 * - Standard comparison operators
 *
 * Usage:
 * ```php
 * protected array $filterRules = [
 *     'Name' => CommonFilters::startsWith('name'),
 *     'Country' => CommonFilters::startsWith('for_country'),
 *     'Category' => CommonFilters::jsonLikeRelation('category', 'category'),
 * ];
 * ```
 */
class CommonFilters
{
    /**
     * Escape SQL LIKE wildcards to prevent injection.
     */
    public static function escapeLikeWildcards(string $value): string
    {
        return str_replace(['%', '_'], ['\\%', '\\_'], $value);
    }

    /**
     * Create a "starts with" filter for a column.
     *
     * @param  string  $column  The column to filter
     */
    public static function startsWith(string $column): Closure
    {
        return function (Builder $query, $value) use ($column) {
            $escaped = self::escapeLikeWildcards($value);

            return $query->where($column, 'like', "{$escaped}%");
        };
    }

    /**
     * Create a "contains" filter for a column.
     *
     * @param  string  $column  The column to filter
     */
    public static function contains(string $column): Closure
    {
        return function (Builder $query, $value) use ($column) {
            $escaped = self::escapeLikeWildcards($value);

            return $query->where($column, 'like', "%{$escaped}%");
        };
    }

    /**
     * Create an exact match filter for a column.
     *
     * @param  string  $column  The column to filter
     */
    public static function exact(string $column): Closure
    {
        return fn (Builder $query, $value) => $query->where($column, $value);
    }

    /**
     * Create a JSON "like" filter for translatable fields.
     *
     * Casts JSON column to text and searches with LIKE for JSONB compatibility.
     *
     * @param  string  $column  The JSON column to filter
     */
    public static function jsonLike(string $column): Closure
    {
        return function (Builder $query, $value) use ($column) {
            $escaped = self::escapeLikeWildcards($value);

            return $query->whereRaw("{$column}::text ILIKE ?", ["%{$escaped}%"]);
        };
    }

    /**
     * Create a filter through a relationship with starts-with matching.
     *
     * @param  string  $relation  The relationship name
     * @param  string  $column  The column in the related model
     */
    public static function relationStartsWith(string $relation, string $column): Closure
    {
        return function (Builder $query, $value) use ($relation, $column) {
            $escaped = self::escapeLikeWildcards($value);

            return $query->whereHas($relation, fn ($q) => $q->where($column, 'like', "{$escaped}%"));
        };
    }

    /**
     * Create a filter through a relationship with JSON like matching.
     *
     * Useful for filtering on translatable fields in related models.
     *
     * @param  string  $relation  The relationship name
     * @param  string  $column  The JSON column in the related model
     */
    public static function jsonLikeRelation(string $relation, string $column): Closure
    {
        return function (Builder $query, $value) use ($relation, $column) {
            $escaped = self::escapeLikeWildcards($value);

            return $query->whereHas($relation, fn ($q) => $q->whereRaw("{$column}::text ILIKE ?", ["%{$escaped}%"]));
        };
    }

    /**
     * Create a boolean filter.
     *
     * Converts various truthy/falsy values to boolean for comparison.
     *
     * @param  string  $column  The column to filter
     */
    public static function boolean(string $column): Closure
    {
        return fn (Builder $query, $value) => $query->where($column, filter_var($value, FILTER_VALIDATE_BOOLEAN));
    }

    /**
     * Create a date range filter (date is on or after the given value).
     *
     * @param  string  $column  The date column to filter
     */
    public static function dateFrom(string $column): Closure
    {
        return fn (Builder $query, $value) => $query->whereDate($column, '>=', $value);
    }

    /**
     * Create a date range filter (date is on or before the given value).
     *
     * @param  string  $column  The date column to filter
     */
    public static function dateTo(string $column): Closure
    {
        return fn (Builder $query, $value) => $query->whereDate($column, '<=', $value);
    }

    /**
     * Create a null/not-null filter.
     *
     * @param  string  $column  The column to filter
     * @param  bool  $isNull  True to filter for null, false for not null
     */
    public static function nullCheck(string $column, bool $isNull = true): Closure
    {
        return fn (Builder $query, $value) => $isNull
            ? $query->whereNull($column)
            : $query->whereNotNull($column);
    }

    /**
     * Create a filter that checks if value is in a list.
     *
     * @param  string  $column  The column to filter
     */
    public static function whereIn(string $column): Closure
    {
        return function (Builder $query, $value) use ($column) {
            $values = is_array($value) ? $value : explode(',', $value);

            return $query->whereIn($column, $values);
        };
    }

    /**
     * Create a comparison filter with operator.
     *
     * @param  string  $column  The column to filter
     * @param  string  $operator  The comparison operator (=, >, <, >=, <=, <>)
     */
    public static function compare(string $column, string $operator = '='): Closure
    {
        // Validate operator to prevent injection
        $allowedOperators = ['=', '>', '<', '>=', '<=', '<>', '!='];
        if (! in_array($operator, $allowedOperators, true)) {
            $operator = '=';
        }

        return fn (Builder $query, $value) => $query->where($column, $operator, $value);
    }
}
