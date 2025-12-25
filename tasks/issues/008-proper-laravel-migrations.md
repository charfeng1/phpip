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

### Phase 1: Baseline Migration (Priority: HIGH)

- [ ] **Task 1.1**: Create baseline migration that loads `postgres-schema.sql`
  - Use `DB::unprepared(file_get_contents(...))` to load schema
  - Handle PostgreSQL-specific syntax
  - Skip if tables already exist (for existing installations)

- [ ] **Task 1.2**: Update `RefreshDatabase` compatibility
  - Test that `php artisan migrate:fresh` works
  - Verify all tables, views, functions, triggers created
  - Ensure seeders run correctly after migration

- [ ] **Task 1.3**: Update test infrastructure
  - Remove dependency on `setup-test-db.sh` for basic tests
  - Keep script for manual database reset if needed
  - Update CI/CD to use standard `migrate:fresh --seed`

### Phase 2: Proper Table Migrations (Priority: MEDIUM)

Break down `postgres-schema.sql` into individual migrations:

- [ ] **Task 2.1**: Tier 1 migrations (7 foundation tables)
- [ ] **Task 2.2**: Tier 2 migrations (5 first-level FK tables)
- [ ] **Task 2.3**: Tier 3 migrations (4 business core tables)
- [ ] **Task 2.4**: Tier 4 migrations (4 relationship tables)
- [ ] **Task 2.5**: Views migration (5 views)
- [ ] **Task 2.6**: Functions migration (6 stored procedures)
- [ ] **Task 2.7**: Triggers migration (7 triggers)

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
