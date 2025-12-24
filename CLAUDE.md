# Database Schema Changes: Always Use Migrations

When modifying the database schema (adding tables, columns, indexes, etc.), **always create a new Laravel migration**. Never modify `database/schema/postgres-schema.sql` directly.

```bash
php artisan make:migration add_column_to_table_name
```

**Why this matters:** The `postgres-schema.sql` file is a frozen snapshot from the MySQL-to-PostgreSQL migration. All subsequent changes must be migrations so they are:
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

### Authentication & Authorization
- Role-based: DBA (admin), DBRW (read-write), DBRO (read-only), CLI (client)
- Use `Gate::authorize()` or `$this->authorize()` in controllers
- Policies in `app/Policies/`

### Code Patterns
- Use enums from `app/Enums/` (ActorRole, EventCode, CategoryCode, etc.)
- Controllers use `HandlesAuditFields` trait for creator/updater tracking
- Form requests in `app/Http/Requests/` for validation

## Testing
- Tests require the PostgreSQL schema to be loaded first
- Use `DatabaseTransactions` trait, not `RefreshDatabase`
- Run: `php artisan test`

## Common Commands
```bash
php artisan serve          # Start dev server
php artisan test           # Run tests
php artisan migrate        # Apply new migrations
npm run dev               # Vite dev server
npm run build             # Production build
```
