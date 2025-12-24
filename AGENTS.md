# Instructions for AI Agents

This file contains critical instructions for AI agents (Claude, GPT, Copilot, etc.) working on this codebase.

---

## Database Schema Changes: Always Use Migrations

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

## Testing Infrastructure

### The Problem
This project does NOT have complete "from scratch" Laravel migrations. The database schema was originally created via SQL dump, not migrations. The migrations that exist are incremental patches.

### The Solution
Tests should:
1. Use `DatabaseTransactions` trait (NOT `RefreshDatabase`)
2. Assume the database schema already exists
3. Roll back changes after each test

```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MyTest extends TestCase
{
    use DatabaseTransactions;  // NOT RefreshDatabase

    // ...
}
```

### Why RefreshDatabase Fails
`RefreshDatabase` tries to run all migrations from scratch, but there's no migration to create the base tables (matter, actor, event, etc.) - those come from `postgres-schema.sql`.

---

## Key Architecture Notes

### Users Table is a VIEW
The `users` table is a PostgreSQL VIEW on the `actor` table, not a real table. This means:
- User factories must create actors with appropriate fields
- Some standard Laravel auth assumptions don't apply

### Translatable Fields
Many fields use JSONB for translations:
```php
// Stored as: {"en": "Patent", "fr": "Brevet"}
$category->category  // Returns localized string via accessor
```

### Enums
Use enums from `app/Enums/` instead of magic strings:
```php
use App\Enums\EventCode;
use App\Enums\ActorRole;

$event->code === EventCode::FILING->value  // Good
$event->code === 'FIL'                      // Avoid
```

---

## Common Pitfalls

1. **Don't use `Schema::create()` for existing tables** - They already exist from the SQL dump
2. **Don't assume standard Laravel auth** - Users are actors with a role
3. **Don't modify postgres-schema.sql** - Use migrations instead
4. **Don't use RefreshDatabase in tests** - Use DatabaseTransactions
