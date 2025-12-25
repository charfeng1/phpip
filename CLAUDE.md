# Database Schema Management

## Migrations
The database schema is managed entirely through **proper Laravel migrations** in `database/migrations/`. The migrations are organized in tiers by dependency order:

| Migration | Contents |
|-----------|----------|
| `000002_create_extensions` | PostgreSQL uuid-ossp extension |
| `000010_create_tier1_*` | Foundation tables (no FK dependencies) |
| `000020_create_tier2_*` | First-level FK tables |
| `000030_create_tier3_*` | Business core tables |
| `000040_create_tier4_*` | Relationship/junction tables |
| `000050_create_tier5_*` | Laravel standard tables |
| `000060_create_tier6_*` | Audit logging |
| `000070_create_stored_functions` | 6 stored procedures |
| `000080_create_views` | 5 views (including `users` for auth) |
| `000090_create_triggers` | 7 triggers |

**Reference documentation:** `database/schema/postgres-schema.sql` is kept as reference only.

**Creating new migrations:**
```bash
php artisan make:migration add_column_to_table_name
```

**Why this approach works:**
- Standard Laravel workflow: `php artisan migrate:fresh --seed`
- `RefreshDatabase` trait works in tests
- Version controlled and reviewable in PRs
- Rollback-able when needed

---

# phpIP Project Guidelines

## Overview
phpIP is an intellectual property management system built with Laravel 12 and PostgreSQL.

## Key Architecture Decisions

### Database
- PostgreSQL with JSONB for translatable fields
- `users` is a VIEW on the `actor` table (not a standard Laravel users table)
- Translations stored as JSON: `{"en": "English", "fr": "Français"}`
- Schema managed through tier-based migrations (see above)

### Authentication & Authorization
- Role-based: DBA (admin), DBRW (read-write), DBRO (read-only), CLI (client)
- Use `Gate::authorize()` or `$this->authorize()` in controllers
- Policies in `app/Policies/`

### Code Patterns
- Use enums from `app/Enums/` (ActorRole, EventCode, CategoryCode, etc.)
- Controllers use `HandlesAuditFields` trait for creator/updater tracking
- Form requests in `app/Http/Requests/` for validation

## Testing
- Run `php artisan migrate:fresh --env=testing --seed` to set up test database
- Use `DatabaseTransactions` trait for most tests (faster)
- Use `RefreshDatabase` trait when testing migrations or needing clean slate
- Run: `php artisan test`

## Common Commands
```bash
php artisan serve                           # Start dev server
php artisan test                            # Run tests
php artisan migrate                         # Apply new migrations
php artisan migrate:fresh --seed            # Reset database with seed data
npm run dev                                 # Vite dev server
npm run build                               # Production build
```
