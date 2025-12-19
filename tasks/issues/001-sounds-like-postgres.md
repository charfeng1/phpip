# Issue: SOUNDS LIKE is MySQL-only - breaks PostgreSQL

**Labels:** bug, postgresql, database

## Problem

`SOUNDS LIKE` syntax in `MatterController::importEPO()` is MySQL-specific and will fail on PostgreSQL.

**Location:** `app/Http/Controllers/MatterController.php` lines 494, 530

```php
Actor::whereRaw("name SOUNDS LIKE ?", [$applicant])
```

## Solution

PostgreSQL requires the `fuzzystrmatch` extension and uses `SOUNDEX()` function:

```php
// Database-agnostic approach
$driver = DB::connection()->getDriverName();
if ($driver === 'pgsql') {
    $actor = Actor::whereRaw("SOUNDEX(name) = SOUNDEX(?)", [$value])->first();
} else {
    $actor = Actor::whereRaw("name SOUNDS LIKE ?", [$value])->first();
}
```

**Note:** Requires enabling `fuzzystrmatch` extension in PostgreSQL:
```sql
CREATE EXTENSION IF NOT EXISTS fuzzystrmatch;
```

## Impact
- Affects EPO import functionality only
- Core matter/task management unaffected

## Priority
Medium - only affects one import feature
