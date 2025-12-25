# Database Schema Management

## Baseline Schema
The file `database/schema/postgres-schema.sql` is the **authoritative baseline** for the database schema. A baseline migration (`0001_01_01_000001_create_baseline_schema.php`) loads this schema, enabling standard Laravel workflows like `php artisan migrate:fresh`.

**When to modify `postgres-schema.sql`:**
- Fixing bugs or omissions in the baseline (missing columns, wrong types)
- Pre-production corrections to align schema with models/factories/seeders

**When to create a new migration instead:**
- Adding new features after the baseline is established
- Any changes in a production environment
- Changes that need to be tracked separately for review

```bash
php artisan make:migration add_column_to_table_name
```

**Why migrations matter:**
- Version controlled and reviewable in PRs
- Automatically applied during deployment
- Rollback-able if needed
- Compatible with the test infrastructure

---

# phpIP Project Guidelines

## Overview
phpIP is an intellectual property management system built with Laravel 12 and PostgreSQL.

## Key Architecture Decisions

### Database
- PostgreSQL with JSONB for translatable fields
- `users` is a VIEW on the `actor` table (not a standard Laravel users table)
- Translations stored as JSON: `{"en": "English", "fr": "Français"}`
- Baseline migration loads complete schema from `postgres-schema.sql`

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
