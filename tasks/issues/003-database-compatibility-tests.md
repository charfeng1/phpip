# Issue: Add database compatibility tests

**Labels:** testing, postgresql, mysql

## Context

The application now supports both MySQL and PostgreSQL. Tests should verify queries work on both databases.

## Approach

### Option A: Dual Database CI (Recommended)
Run test suite against both MySQL and PostgreSQL in CI:

```yaml
# .github/workflows/test.yml
jobs:
  test:
    strategy:
      matrix:
        database: [mysql, pgsql]
    services:
      mysql:
        image: mysql:8.0
        # ...
      postgres:
        image: postgres:15
        # ...
```

### Option B: Database-specific test traits
```php
trait TestsMultipleDatabases
{
    /** @dataProvider databaseProvider */
    public function test_matter_crud($driver)
    {
        config(['database.default' => $driver]);
        // ... test code
    }

    public function databaseProvider()
    {
        return [['mysql'], ['pgsql']];
    }
}
```

## Key Areas to Test
- [ ] JSON column queries (jsonWhereLike, JSON_EXTRACT vs ->>)
- [ ] Date functions (DATE_FORMAT vs TO_CHAR)
- [ ] String aggregation (GROUP_CONCAT vs STRING_AGG)
- [ ] Case sensitivity (LIKE vs ILIKE)
- [ ] Renewal year extraction from JSON

## Priority
Medium - important for dual-database support confidence
