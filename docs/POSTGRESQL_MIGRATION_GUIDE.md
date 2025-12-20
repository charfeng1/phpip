# PostgreSQL Migration Guide

## Overview

This guide documents PostgreSQL-specific considerations and migration paths for the phpIP system.

## CHAR Column Handling

### Current Approach: Runtime Trimming

PostgreSQL CHAR columns are fixed-length and pad values with spaces to fill the defined width. To handle this:

**Implementation**: The `TrimsCharColumns` trait automatically trims CHAR column values at runtime.

**Models using this trait:**
- `Actor`: default_role, login, language, country, nationality, country_mailing, country_billing
- `Matter`: category_code, country, origin, type_code
- `User`: default_role, login, language

**Benefits:**
- Maintains compatibility with existing MySQL deployments
- No schema changes required
- Centralized trimming logic in a single trait
- NULL handling: preserves NULL values, converts whitespace-only to empty string

**Usage in new models:**
```php
use App\Traits\TrimsCharColumns;

class YourModel extends Model
{
    use TrimsCharColumns;

    protected $charColumns = [
        'your_char_column',
        'another_char_column',
    ];
}
```

### Alternative: CHAR → VARCHAR Migration

For PostgreSQL-only deployments, converting CHAR to VARCHAR eliminates padding at the source.

**Benefits:**
- No runtime overhead for trimming
- More natural PostgreSQL data types
- Consistent behavior across queries

**Drawbacks:**
- Breaks MySQL compatibility
- Requires schema migration
- May affect existing queries/code expecting fixed-length values

**Migration SQL (PostgreSQL):**
```sql
-- Example: Convert actor CHAR columns to VARCHAR
ALTER TABLE actor
  ALTER COLUMN default_role TYPE VARCHAR(5),
  ALTER COLUMN login TYPE VARCHAR(16),
  ALTER COLUMN language TYPE VARCHAR(2),
  ALTER COLUMN country TYPE VARCHAR(2),
  ALTER COLUMN nationality TYPE VARCHAR(2),
  ALTER COLUMN country_mailing TYPE VARCHAR(2),
  ALTER COLUMN country_billing TYPE VARCHAR(2);

ALTER TABLE matter
  ALTER COLUMN category_code TYPE VARCHAR(5),
  ALTER COLUMN country TYPE VARCHAR(2),
  ALTER COLUMN origin TYPE VARCHAR(2),
  ALTER COLUMN type_code TYPE VARCHAR(5);

-- Continue for other tables with CHAR columns
```

**Recommendation:** Use the `TrimsCharColumns` trait approach unless you have a PostgreSQL-only deployment and require maximum performance.

## Performance Indexes

### Matter Table Indexes

The following indexes have been added to improve query performance:

```sql
-- Index for filtering by dead status
CREATE INDEX idx_matter_dead ON matter(dead);

-- Composite index for family queries filtered by status
CREATE INDEX idx_matter_caseref_dead ON matter(caseref, dead);
```

**Query patterns optimized:**
- `WHERE dead = false` - Active matters filtering
- `WHERE caseref = ? AND dead = false` - Active family members
- Matter list views with status filtering

### Performance Monitoring

For queries using `STRING_AGG` (PostgreSQL) or `GROUP_CONCAT` (MySQL):

**PostgreSQL-specific considerations:**
- `STRING_AGG` can be slower with large datasets (>10,000 rows per group)
- Monitor query performance on aggregated actor/classifier queries
- Consider denormalization or caching for frequently-accessed aggregations

**Monitoring queries:**
```sql
-- Check slow queries with STRING_AGG
SELECT query, mean_exec_time, calls
FROM pg_stat_statements
WHERE query LIKE '%STRING_AGG%'
ORDER BY mean_exec_time DESC
LIMIT 10;

-- Check index usage on matter table
SELECT
    schemaname,
    tablename,
    indexname,
    idx_scan,
    idx_tup_read,
    idx_tup_fetch
FROM pg_stat_user_indexes
WHERE tablename = 'matter'
ORDER BY idx_scan DESC;
```

## Schema Validation

### Ensuring Schema Consistency

**Database schema files:**
- MySQL: Migrations in `database/migrations/`
- PostgreSQL: `database/schema/postgres-schema.sql`

**Validation checklist:**
1. CHAR column widths match between MySQL and PostgreSQL schemas
2. Foreign key constraints are consistent
3. Index definitions are equivalent (accounting for syntax differences)
4. JSON/JSONB column types are properly mapped
5. Date/datetime handling is consistent

**Automated validation (future improvement):**
```php
// Suggested artisan command: php artisan schema:validate
// This would compare model expectations with actual database schema
```

**Manual validation:**
```sql
-- PostgreSQL: Check column types
SELECT
    table_name,
    column_name,
    data_type,
    character_maximum_length
FROM information_schema.columns
WHERE table_schema = 'public'
  AND table_name IN ('actor', 'matter', 'event', 'task')
ORDER BY table_name, ordinal_position;

-- Check indexes
SELECT
    tablename,
    indexname,
    indexdef
FROM pg_indexes
WHERE schemaname = 'public'
  AND tablename IN ('actor', 'matter', 'event', 'task')
ORDER BY tablename, indexname;
```

## Breaking Changes

### Agent → AgentName Alias (PR #9)

**Change:** The `agent` relationship in models may have been renamed or aliased.

**Impact:**
- Code referencing `$matter->agent` may need updating
- Views and templates using agent relationships

**Migration path:**
1. Search codebase for `->agent` or `['agent']` accessor usage
2. Update to use the new relationship name
3. Test all views and controllers that display agent information

**Backward compatibility:**
```php
// Add an alias in the model for backward compatibility
public function agent()
{
    return $this->agentName();
}
```

## Database-Specific Features

### PostgreSQL Features Used

1. **JSONB columns**: Better performance than JSON for queries
2. **Recursive CTEs**: Used in `TeamService` for hierarchy traversal
3. **String aggregation**: `STRING_AGG()` for concatenating related records
4. **CHECK constraints**: Can be added for data validation

### MySQL Compatibility Notes

When writing queries that must work on both databases:

**Aggregation:**
```php
// Use database-agnostic approach
DB::connection()->getDriverName() === 'pgsql'
    ? "STRING_AGG(name, ', ')"
    : "GROUP_CONCAT(name SEPARATOR ', ')";
```

**JSON operations:**
```php
// PostgreSQL JSONB vs MySQL JSON
// Use Laravel's JSON cast for cross-compatibility
protected $casts = [
    'detail' => 'array', // Handles both automatically
];
```

## Recommendations

### For New Installations

1. **PostgreSQL (Supabase):**
   - Use the provided `postgres-schema.sql`
   - Apply all migrations in order
   - Consider CHAR→VARCHAR conversion if MySQL compatibility not needed

2. **MySQL:**
   - Run migrations from `database/migrations/`
   - No additional configuration needed

### For Existing Installations

1. **Backup your database** before any schema changes
2. Run new migrations: `php artisan migrate`
3. Test CHAR trimming behavior in your application
4. Monitor query performance after adding indexes
5. Review and update any custom queries using CHAR columns

### Performance Tuning

1. **Enable query logging** during development:
   ```php
   DB::enableQueryLog();
   // ... your code ...
   dd(DB::getQueryLog());
   ```

2. **Profile slow queries** in production:
   - PostgreSQL: Enable `pg_stat_statements`
   - MySQL: Enable slow query log

3. **Add indexes** based on actual query patterns:
   - Check `EXPLAIN` output for full table scans
   - Monitor index usage statistics
   - Remove unused indexes

## Future Improvements

### Planned Enhancements

- [ ] Automated schema validation command
- [ ] Database-agnostic query builder helpers
- [ ] Performance monitoring dashboard
- [ ] Automated index recommendations based on query logs
- [ ] Migration path generator for CHAR→VARCHAR conversion

### Contributing

When adding new database-specific features:

1. Document PostgreSQL vs MySQL differences
2. Add database checks where necessary
3. Update this guide with migration notes
4. Test on both database platforms if possible

---

**Last Updated:** 2025-12-20
**Related Issues:** #10 (PostgreSQL compatibility follow-up)
**Related PRs:** #9 (PostgreSQL compatibility refactor)
