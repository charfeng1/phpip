# Issue 008: Create Proper Laravel Migrations for Full Database Schema

## Problem Statement

Currently, phpIP uses a **schema dump approach** (`database/schema/postgres-schema.sql`) rather than proper Laravel migrations. This causes several issues:

1. **`RefreshDatabase` doesn't work** - Tests must use `DatabaseTransactions` instead
2. **Non-standard Laravel workflow** - New developers need to run `./tests/setup-test-db.sh`
3. **CI/CD complexity** - Special handling required for test database setup
4. **Migration drift risk** - Incremental migrations may diverge from the schema dump

## Current State

- **Schema source**: `database/schema/postgres-schema.sql` (frozen baseline from MySQL→PostgreSQL migration)
- **Existing migrations**: 9 incremental migrations that modify the baseline
- **Test setup**: Requires manual script execution before tests work

## Proposed Solution

Create a **baseline migration** that can rebuild the entire database from scratch, enabling standard Laravel workflows.

### Approach: Baseline Migration Strategy

1. Create a single migration that loads `postgres-schema.sql` as the baseline
2. Existing incremental migrations continue to work on top
3. Eventually break down into proper table-by-table migrations (optional, lower priority)

This gives us:
- `RefreshDatabase` works immediately
- Standard Laravel workflow
- No risk of missing schema details
- Can refactor the baseline later when needed

---

## Database Analysis Summary

### Tables (24 total)

| Tier | Tables | Description |
|------|--------|-------------|
| 1 | 7 | Foundation tables (no FK dependencies) |
| 2 | 5 | First-level FK dependencies |
| 3 | 4 | Business core tables |
| 4 | 4 | Relationship/junction tables |
| 5 | 3 | Laravel standard tables |
| 6 | 1 | Audit logging |

### Views (5 total)

| View | Purpose |
|------|---------|
| `users` | **CRITICAL**: Laravel auth reads from this view on `actor` table |
| `event_lnk_list` | Priority claim linking |
| `matter_actors` | Aggregated actor relationships |
| `matter_classifiers` | Aggregated classifiers |
| `task_list` | Task display view |

### Stored Procedures (6 total)

- `tcase(TEXT)` - Title case converter
- `actor_list(INTEGER, TEXT)` - Actor aggregation
- `matter_status(INTEGER)` - Current matter status
- `compute_matter_uid(...)` - UID generation
- `insert_recurring_renewals(...)` - Renewal task creation
- `update_expired()` - Expiry event marking

### Triggers (7 total)

- `classifier_before_insert` - Auto-capitalize titles
- `event_before_insert/update` - Event date computation
- `matter_before_insert/update` - UID computation, timestamps
- `task_before_insert/update` - Auto-completion, done_date management

---

## Migration Dependency Order

```
TIER 1 - Foundation (No FK):
├── country
├── actor_role
├── matter_category
├── matter_type
├── event_name
├── classifier_type
└── template_classes

TIER 2 - First-Level FK:
├── actor (→ country, actor_role)
├── classifier_value (→ classifier_type)
├── template_members (→ template_classes)
├── fees (→ country, matter_category)
└── default_actor (→ actor, actor_role, country, matter_category)

TIER 3 - Business Core:
├── matter (→ matter_category, country, matter_type)
├── event (→ event_name, matter)
├── task_rules (→ country, matter_category, matter_type, event_name)
└── task (→ event, event_name, task_rules)

TIER 4 - Relationships:
├── matter_actor_lnk (→ matter, actor, actor_role)
├── classifier (→ matter, classifier_type, classifier_value)
├── event_class_lnk (→ event_name, template_classes)
└── renewals_logs (→ task)

TIER 5 - Laravel Standard:
├── migrations
├── password_resets
└── failed_jobs

TIER 6 - Audit:
└── audit_logs

POST-SCHEMA:
├── Views (users, event_lnk_list, matter_actors, matter_classifiers, task_list)
├── Functions (6 stored procedures)
├── Triggers (7 triggers)
└── Seed reference data
```

---

## Special Considerations

### 1. The `users` View Pattern
- Laravel expects a `users` table, but phpIP uses `actor` table
- A VIEW named `users` filters `actor WHERE login IS NOT NULL`
- **Must create view AFTER actor table exists**

### 2. JSONB Translations
- Translatable fields stored as JSONB: `{"en": "...", "fr": "...", "zh": "..."}`
- Use `json()` column type in migrations

### 3. Complex Unique Constraints
- Some constraints use `COALESCE()` in definitions
- Requires `DB::statement()` for raw SQL

### 4. Self-Referencing Foreign Keys
- `actor.parent_id → actor.id`
- `matter.parent_id → matter.id`
- Must handle carefully in migrations

### 5. PostgreSQL-Specific Features
- `SERIAL` / `BIGSERIAL` types
- `uuid-ossp` extension
- Trigger syntax differs from MySQL

---

## Implementation Tasks

### Phase 1: Baseline Migration (Priority: HIGH) ✅ COMPLETED

> **Completed**: PR #35 (commit 460929f) - 2024-12-25

- [x] **Task 1.1**: Create baseline migration that loads `postgres-schema.sql`
  - Created `0001_01_01_000001_create_baseline_schema.php`
  - Uses `DB::unprepared(file_get_contents(...))` to load schema
  - Skips if tables already exist (for existing installations)
  - Includes proper `down()` method for rollback

- [x] **Task 1.2**: Update `RefreshDatabase` compatibility
  - `php artisan migrate:fresh` now works
  - All tables, views, functions, triggers created
  - Seeders run correctly after migration

- [x] **Task 1.3**: Update test infrastructure
  - Created `BaselineMigrationTest.php` with 47 tests
  - Tests use `RefreshDatabase` trait successfully
  - Verifies all 24 tables, 5 views, 13 functions, 7 triggers

### Phase 2: Proper Table Migrations (Priority: MEDIUM) - IN PROGRESS

Break down `postgres-schema.sql` into individual migrations:

- [x] **Task 2.1**: Tier 1 migrations (7 foundation tables)
  - Created `0001_01_01_000010_create_tier1_foundation_tables.php`
  - Tables: country, actor_role, matter_category, matter_type, event_name, classifier_type, template_classes
- [x] **Task 2.2**: Tier 2 migrations (5 first-level FK tables)
  - Created `0001_01_01_000020_create_tier2_first_level_fk_tables.php`
  - Tables: actor, classifier_value, template_members, fees, default_actor
- [x] **Task 2.3**: Tier 3 migrations (4 business core tables)
  - Created `0001_01_01_000030_create_tier3_business_core_tables.php`
  - Tables: matter, event, task_rules, task
- [x] **Task 2.4**: Tier 4 migrations (4 relationship tables)
  - Created `0001_01_01_000040_create_tier4_relationship_tables.php`
  - Tables: matter_actor_lnk, classifier, event_class_lnk, renewals_logs
- [x] **Task 2.5**: Views migration (5 views)
  - Created `0001_01_01_000080_create_views.php`
  - Views: users, event_lnk_list, matter_actors, matter_classifiers, task_list
- [x] **Task 2.6**: Functions migration (6 stored procedures)
  - Created `0001_01_01_000070_create_stored_functions.php`
  - Functions: tcase, actor_list, matter_status, compute_matter_uid, insert_recurring_renewals, update_expired
- [x] **Task 2.7**: Triggers migration (7 triggers)
  - Created `0001_01_01_000090_create_triggers.php`
  - Triggers: classifier_before_insert, event_before_insert/update, matter_before_insert/update, task_before_insert/update

**Additional migrations created:**
- `0001_01_01_000002_create_extensions.php` - PostgreSQL uuid-ossp extension
- `0001_01_01_000050_create_tier5_laravel_standard_tables.php` - password_resets, failed_jobs
- `0001_01_01_000060_create_tier6_audit_table.php` - audit_logs table

**Test file created:**
- `tests/Feature/Migrations/TierMigrationsTest.php` - Data provider-based tests for all tiers

### Phase 3: Cleanup (Priority: LOW)

- [ ] **Task 3.1**: Remove `postgres-schema.sql` dependency
- [ ] **Task 3.2**: Update documentation
- [ ] **Task 3.3**: Archive or remove `setup-test-db.sh`

---

## Complexity Assessment

**Overall: HIGH (9/10)**

| Factor | Complexity | Notes |
|--------|------------|-------|
| FK Interdependency | 8/10 | 24 foreign keys, circular references |
| Functions/Triggers | 9/10 | 6 procedures, 7 triggers with complex logic |
| PostgreSQL-Specific | 8/10 | JSONB, SERIAL, trigger syntax |
| Views as Data Source | 8/10 | `users` view critical for auth |
| Unique Constraints | 7/10 | COALESCE in constraints |

---

## Success Criteria

1. `php artisan migrate:fresh` creates complete database
2. `php artisan migrate:fresh --seed` populates reference data
3. `RefreshDatabase` trait works in tests
4. All existing tests pass
5. No manual script execution required for development setup
6. CI/CD uses standard Laravel commands

---

## References

- Current schema: `database/schema/postgres-schema.sql`
- Test setup script: `tests/setup-test-db.sh`
- Existing migrations: `database/migrations/`
- Related issue: Test infrastructure uses `DatabaseTransactions` workaround
