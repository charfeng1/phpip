<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

/**
 * Helper trait for database-agnostic JSON operations.
 *
 * Provides methods that work across MySQL and PostgreSQL for common
 * JSON column operations like extraction and aggregation.
 */
trait DatabaseJsonHelper
{
    /**
     * Get the current database driver name.
     *
     * @return string
     */
    protected function getDbDriver(): string
    {
        return DB::connection()->getDriverName();
    }

    /**
     * Check if using PostgreSQL.
     *
     * @return bool
     */
    protected function isPostgres(): bool
    {
        return $this->getDbDriver() === 'pgsql';
    }

    /**
     * Get a raw SQL expression for extracting a JSON text value.
     *
     * @param string $column The JSON column name
     * @param string $key The JSON key to extract
     * @param string|null $alias Optional alias for the result
     * @return \Illuminate\Database\Query\Expression
     */
    protected static function jsonExtract(string $column, string $key, ?string $alias = null): \Illuminate\Database\Query\Expression
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            $sql = "{$column} ->> '{$key}'";
        } else {
            // MySQL
            $sql = "JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.\"$key\"'))";
        }

        if ($alias) {
            $sql .= " AS {$alias}";
        }

        return DB::raw($sql);
    }

    /**
     * Get a raw SQL expression for extracting a JSON numeric value as integer.
     *
     * @param string $column The JSON column name
     * @param string $key The JSON key to extract
     * @return \Illuminate\Database\Query\Expression
     */
    protected static function jsonExtractInt(string $column, string $key): \Illuminate\Database\Query\Expression
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return DB::raw("({$column} ->> '{$key}')::INTEGER");
        }

        // MySQL
        return DB::raw("CAST(JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.\"$key\"')) AS UNSIGNED)");
    }

    /**
     * Get a raw SQL expression for GROUP_CONCAT equivalent.
     *
     * @param string $expression The expression to concatenate
     * @param string $separator The separator between values
     * @param bool $distinct Whether to use DISTINCT
     * @param string|null $alias Optional alias for the result
     * @return \Illuminate\Database\Query\Expression
     */
    protected static function groupConcat(string $expression, string $separator = ',', bool $distinct = false, ?string $alias = null): \Illuminate\Database\Query\Expression
    {
        $driver = DB::connection()->getDriverName();
        $distinctClause = $distinct ? 'DISTINCT ' : '';

        if ($driver === 'pgsql') {
            // PostgreSQL uses STRING_AGG
            $sql = "STRING_AGG({$distinctClause}{$expression}, '{$separator}')";
        } else {
            // MySQL uses GROUP_CONCAT
            $sql = "GROUP_CONCAT({$distinctClause}{$expression} SEPARATOR '{$separator}')";
        }

        if ($alias) {
            $sql .= " AS {$alias}";
        }

        return DB::raw($sql);
    }

    /**
     * Get raw SQL for JSON-based ordering.
     *
     * @param string $column The JSON column name
     * @param string $key The JSON key to order by
     * @return string
     */
    protected static function jsonOrderBy(string $column, string $key): string
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return "{$column} ->> '{$key}'";
        }

        // MySQL
        return "JSON_UNQUOTE(JSON_EXTRACT({$column}, '$.\"$key\"'))";
    }

    /**
     * Get raw SQL for JSON column WHERE clause with LIKE/ILIKE.
     *
     * @param string $column The JSON column name
     * @param string $key The JSON key to match
     * @param string $value The value pattern to match
     * @return array [sql, bindings]
     */
    protected static function jsonWhereLike(string $column, string $key, string $value): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'pgsql') {
            return [
                "{$column} ->> ? ILIKE ?",
                [$key, "%{$value}%"],
            ];
        }

        // MySQL
        return [
            "JSON_EXTRACT({$column}, '$.{$key}') LIKE ?",
            ["%{$value}%"],
        ];
    }
}
