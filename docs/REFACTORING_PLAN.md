# phpIP Incremental Refactoring Plan

## Philosophy

**Every PR must show immediate LOC reduction or clear value.** No "foundation" PRs that add abstractions without applying them.

## Identified Duplication Patterns

### 1. SQL Wildcard Escaping (19 instances)
```php
// Current: Repeated in 19 places
$escapedValue = str_replace(['%', '_'], ['\\%', '\\_'], $v);
```

**Controllers affected:**
- AuditLogController
- AutocompleteController
- CountryController (2x)
- DefaultActorController (5x)
- DocumentController
- EventController
- FeeController
- MatterController
- RoleController
- RuleController
- TaskController
- TemplateController
- UserController

### 2. JSON LIKE Filters (Multiple instances)
```php
// Current: Various implementations
$q->whereRaw("CAST($column AS TEXT) ILIKE ?", ["%$v%"])
```

### 3. Pagination Pattern (12 controllers)
```php
// Current: Repeated pattern
$results = $query->paginate(config('pagination.default', 21));
$results->appends($request->input())->links();
```

---

## Refactoring Phases

### Phase 1: Extract Wildcard Escaping Helper
**Goal**: Remove 19 duplicate `str_replace` calls
**Approach**: Add one helper method to `Filterable` trait (already used by 12 controllers)

```php
// In Filterable trait, add:
protected function escapeLikeWildcards(string $value): string
{
    return str_replace(['%', '_'], ['\\%', '\\_'], $value);
}
```

**PR Scope**:
- Add helper method to Filterable trait (~5 lines)
- Update all 19 usages (~-38 lines)
- **Net: ~-33 lines**

---

### Phase 2: Standardize Filter Patterns (Optional)
**Goal**: Simplify common filter callbacks
**Approach**: Add helper methods to Filterable trait for common patterns

```php
// In Filterable trait:
protected function startsWithFilter(string $column): Closure
{
    return fn($q, $v) => $q->where($column, 'like', $this->escapeLikeWildcards($v) . '%');
}

protected function containsFilter(string $column): Closure
{
    return fn($q, $v) => $q->where($column, 'like', '%' . $this->escapeLikeWildcards($v) . '%');
}
```

**PR Scope**:
- Add 2 helper methods (~10 lines)
- Update controllers that use simple patterns (~-20 lines)
- **Net: ~-10 lines**

---

### Phase 3: Consolidate Pagination (Optional, Low Priority)
**Goal**: DRY pagination logic
**Assessment**: Current code is already clean with `filterAndPaginate()` method
**Decision**: Skip unless specific pain points emerge

---

## What NOT To Do

1. **No BaseResourceController** - Controllers are already thin, inheritance adds complexity
2. **No CommonFilters service** - Trait methods are simpler and don't require DI
3. **No JS API module** - Current inline fetch is readable and context-specific
4. **No Blade components** - Current views are straightforward

---

## Execution Guidelines

1. **One pattern per PR** - Don't bundle multiple refactors
2. **Include migration in same PR** - Don't add abstractions without using them
3. **Run full test suite** - `php artisan test` must pass
4. **E2E smoke test** - Verify affected pages still work

---

## Priority

| Phase | Effort | Impact | Priority |
|-------|--------|--------|----------|
| Phase 1: Wildcard escaping | Low | High (removes 19 duplicates) | **Do First** |
| Phase 2: Filter helpers | Low | Medium | Optional |
| Phase 3: Pagination | Low | Low | Skip |

---

## Current State Summary

- **Controllers using Filterable**: 12
- **Controllers using HandlesAuditFields**: 16
- **Duplicate wildcard escaping**: 19 instances
- **Test count**: 1325 passing

The codebase is already well-structured. Focus on removing duplication, not adding abstraction layers.
