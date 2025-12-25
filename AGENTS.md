# Instructions for AI Agents

This file contains critical instructions for AI agents (Claude, GPT, Copilot, etc.) working on this codebase.

---

## Database Schema Management

### Tier-Based Migrations
The database schema is managed through tier-based migrations organized by dependency order:
- `000002` - Extensions (uuid-ossp)
- `000010` - Tier 1: Foundation tables (country, actor_role, etc.)
- `000020` - Tier 2: First-level FK tables (actor, classifier_value, etc.)
- `000030` - Tier 3: Business core tables (matter, event, task_rules)
- `000040` - Tier 4: Relationship tables (classifier, matter_actor_lnk, etc.)
- `000050` - Tier 5: Laravel standard tables (jobs, cache, sessions)
- `000060` - Tier 6: Audit table
- `000070` - Stored functions
- `000080` - Views (including critical 'users' view)
- `000090` - Triggers

The file `database/schema/postgres-schema.sql` serves as a **reference document** for the complete schema.

**Adding new schema changes:**
Create a new migration with an appropriate timestamp:
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

1. **Don't use `Schema::create()` for existing tables** - They already exist from the tier migrations
2. **Don't assume standard Laravel auth** - Users are actors with a role
3. **Create migrations for new features** - Use standard Laravel migration timestamps
4. **Use `DatabaseTransactions` for most tests** - Faster than `RefreshDatabase`
