# Phase 1: Auditable Trait and Utility Traits

**Created**: 2025-12-26
**GitHub Issue**: #53
**Priority**: High
**Status**: Completed

## Summary

Implement Phase 1 of code quality improvements. This was originally attempted in PR #49 but failed due to schema incompatibility.

## Problem

PR #49 added the `Auditable` trait to 8 models but caused 156 test failures because:
- `audit_logs.auditable_id` column is `unsignedBigInteger`
- Some models use string primary keys (`code` field): Category, ClassifierType, EventName, MatterType, Role

## Tasks

### 1. Fix audit_logs schema
- [x] Create migration to change `auditable_id` from `bigint` to `string(255)`
- [x] This allows both integer and string PKs to be stored

### 2. Add Auditable trait to models
- [x] ActorPivot
- [x] Category
- [x] ClassifierType
- [x] EventName
- [x] Fee
- [x] MatterType
- [x] Role
- [x] Rule

Each model excludes timestamps from audit:
```php
use App\Traits\Auditable;

protected $auditExclude = ['created_at', 'updated_at'];
```

### 3. Utility traits - SKIPPED

After analysis, the proposed utility traits are not needed because:

- **ParsesDates**: Only 3 usages in the codebase, not significant duplication
- **HasTeamScopes**: Already implemented as `TeamScope` global scope + `TeamService`
- **FiltersWithWhitelist**: Already implemented in service classes (`DocumentFilterService`, `RenewalLogFilterService`)

### 4. Add EventCode enum values
- [x] `ABANDONED = 'ABA'` - Indicates application was abandoned
- [x] `LAPSED = 'LAP'` - Indicates IP right lapsed due to non-renewal
- [x] Added `endOfLifeEvents()` and `isEndOfLife()` helper methods
- [x] Added tests for new enum values

## Acceptance Criteria

- [x] All 1307 tests pass
- [x] Audit logs work for all models (integer and string PKs)
- [x] No breaking changes to existing functionality

## Implementation Notes

- Migration: `2025_12_26_000001_alter_audit_logs_auditable_id_to_string.php`
- Fixed test that used strict comparison with `auditable_id` (now stored as string)

## References

- Original PR: #49 (closed - 156 test failures)
- Analysis document: CODE_QUALITY_ANALYSIS.md (from PR #49)
