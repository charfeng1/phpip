# Instructions for AI Agents

This file contains critical instructions for AI agents (Claude, GPT, Copilot, etc.) working on this codebase.

---

## Database Schema Management

### Baseline Schema
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

## Testing Infrastructure

### Database Setup
Run `php artisan migrate:fresh --env=testing --seed` to set up the test database.

### Test Traits
- **`DatabaseTransactions`** (default, faster): Wraps each test in a transaction, rolls back after. Use for most tests.
- **`RefreshDatabase`**: Full reset each test. Use when testing migrations or needing a completely clean slate.

```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MyTest extends TestCase
{
    use DatabaseTransactions;  // Default for most tests

    // ...
}
```

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;  // For migration tests
    protected $seed = true;  // Seed after migrations

    // ...
}
```

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

1. **Don't use `Schema::create()` for existing tables** - They already exist from the baseline
2. **Don't assume standard Laravel auth** - Users are actors with a role
3. **Create migrations for new features** - After the baseline is established
4. **Use `DatabaseTransactions` for most tests** - Faster than `RefreshDatabase`
