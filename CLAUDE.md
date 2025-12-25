# phpIP Project Guidelines

## Overview

phpIP is an intellectual property management system (docketing system) for managing patents, trademarks, designs, and other IP rights. Built with Laravel 12 and PostgreSQL.

**Repository:** `jjdejong/phpip`
**License:** GPL

---

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12, PHP 8.2+ |
| Database | PostgreSQL with JSONB |
| Frontend | Blade templates, Bootstrap 5, Alpine.js |
| Build | Vite, Sass |
| Testing | PHPUnit 11 |
| Package Manager | Composer, npm |

---

## Database Schema Management

### Tier-Based Migrations

The database schema is managed through **proper Laravel migrations** in `database/migrations/`. Migrations are organized in tiers by dependency order:

| Migration Prefix | Contents |
|------------------|----------|
| `0001_01_01_000002` | PostgreSQL uuid-ossp extension |
| `0001_01_01_000010` | Tier 1: Foundation tables (no FK dependencies) |
| `0001_01_01_000020` | Tier 2: First-level FK tables |
| `0001_01_01_000030` | Tier 3: Business core tables (matter, event, task_rules) |
| `0001_01_01_000040` | Tier 4: Relationship/junction tables |
| `0001_01_01_000050` | Tier 5: Laravel standard tables (jobs, cache, sessions) |
| `0001_01_01_000060` | Tier 6: Audit logging |
| `0001_01_01_000070` | Stored functions (13 PostgreSQL functions) |
| `0001_01_01_000080` | Views (5 views including critical `users` view) |
| `0001_01_01_000090` | Triggers (7 triggers) |
| `2025_*` | Incremental schema updates |

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

### Critical: Users Table is a VIEW

The `users` table is a **PostgreSQL VIEW** on the `actor` table, not a real table:
- User factories must create actors with appropriate fields
- Some standard Laravel auth assumptions don't apply
- The view is defined in the `000080_create_views` migration

---

## Authentication & Authorization

### User Roles

Defined in `App\Enums\UserRole`:

| Role | Code | Permissions |
|------|------|-------------|
| Admin | `DBA` | Full access to all features |
| Read-Write | `DBRW` | Can view and modify data |
| Read-Only | `DBRO` | Can only view data |
| Client | `CLI` | External client, limited access to own matters |

### Authorization Patterns

```php
// In controllers - use Gate
use Illuminate\Support\Facades\Gate;
Gate::authorize('readwrite');
Gate::authorize('admin');

// Or use controller authorize
$this->authorize('update', $matter);

// Policies are in app/Policies/
// Common gates: 'admin', 'readwrite', 'except_client'
```

---

## Enums

Located in `app/Enums/`. **Always use enums instead of magic strings:**

### UserRole
```php
use App\Enums\UserRole;
UserRole::ADMIN->value     // 'DBA'
UserRole::READ_WRITE->value // 'DBRW'
UserRole::READ_ONLY->value  // 'DBRO'
UserRole::CLIENT->value     // 'CLI'
```

### ActorRole
```php
use App\Enums\ActorRole;
ActorRole::CLIENT->value      // 'CLI'
ActorRole::APPLICANT->value   // 'APP'
ActorRole::OWNER->value       // 'OWN'
ActorRole::AGENT->value       // 'AGT'
ActorRole::INVENTOR->value    // 'INV'
```

### EventCode
```php
use App\Enums\EventCode;
EventCode::FILING->value      // 'FIL'
EventCode::PUBLICATION->value // 'PUB'
EventCode::GRANT->value       // 'GRT'
EventCode::RENEWAL->value     // 'REN'
```

### CategoryCode
```php
use App\Enums\CategoryCode;
CategoryCode::PATENT->value    // 'PAT'
CategoryCode::TRADEMARK->value // 'TM'
CategoryCode::DESIGN->value    // 'DES'
```

### ClassifierType
```php
use App\Enums\ClassifierType;
// Title types, image types, etc.
```

---

## Key Traits

Located in `app/Traits/`:

### HandlesAuditFields
Used in controllers to automatically add creator/updater fields:
```php
use App\Traits\HandlesAuditFields;

class MatterController extends Controller
{
    use HandlesAuditFields;

    public function store(Request $request)
    {
        $this->mergeCreator($request);
        // ...
    }

    public function update(Request $request, Matter $matter)
    {
        $this->mergeUpdater($request);
        // ...
    }
}
```

### Auditable
Used in models to automatically log changes to `audit_logs` table:
```php
use App\Traits\Auditable;

class Matter extends Model
{
    use Auditable;

    protected $auditExclude = ['created_at', 'updated_at'];
}
```

### TrimsCharColumns
Automatically trims PostgreSQL CHAR columns (which are padded with spaces):
```php
use App\Traits\TrimsCharColumns;

class Matter extends Model
{
    use TrimsCharColumns;

    protected $charColumns = ['category_code', 'country', 'type_code'];
}
```

### Other Traits
- `Filterable` - Query filtering capabilities
- `HasActorsFromRole` - Get actors by role from matter
- `DatabaseJsonHelper` - JSONB field handling
- `HasTranslationsExtended` - Extended translation support

---

## Core Models

Located in `app/Models/`:

### Matter (Central Model)
The core model representing an IP case:
- Belongs to Category, Country, MatterType
- Has many Events, Tasks, Classifiers
- Has many Actors through ActorPivot
- Can be a container (family) with child matters

### Actor
Represents people/organizations:
- Can have roles in matters (client, agent, inventor, etc.)
- The `users` view is based on actors with `login` field

### Event
Lifecycle events (filing, publication, grant, etc.):
- Has associated Tasks (deadlines/reminders)
- Linked to EventName for type definition

### Task
Deadlines and reminders:
- Generated from Rules based on Events
- Has due dates, responsible users, etc.

### Other Models
- `Category` - IP type (Patent, Trademark, etc.)
- `Country` - Jurisdictions
- `Classifier` - Titles, keywords, classes
- `Rule` - Business rules for task generation
- `Fee` - Renewal fee schedules
- `AuditLog` - Change tracking

---

## Services

Located in `app/Services/`:

| Service | Purpose |
|---------|---------|
| `MatterExportService` | Export matter data |
| `MatterOperationService` | Matter CRUD operations |
| `PatentFamilyCreationService` | Create patent families |
| `RenewalWorkflowService` | Renewal processing workflow |
| `RenewalFeeCalculatorService` | Calculate renewal fees |
| `RenewalLogService` | Log renewal activities |
| `RenewalNotificationService` | Send renewal notifications |
| `DocumentMergeService` | Merge documents with templates |
| `OPSService` | EPO Open Patent Services integration |
| `DolibarrInvoiceService` | Dolibarr ERP integration |
| `TeamService` | Team/hierarchy management |

---

## Translatable Fields

Many fields use JSONB for multi-language translations with `spatie/laravel-translatable`:

```php
// Stored as: {"en": "Patent", "fr": "Brevet"}
$category->category  // Returns localized string via accessor
$category->setTranslation('category', 'fr', 'Brevet');
$category->getTranslation('category', 'en');
```

---

## Testing

### Setup
```bash
php artisan migrate:fresh --env=testing --seed
php artisan test
```

### Test Traits

**DatabaseTransactions** (default, faster):
```php
use Illuminate\Foundation\Testing\DatabaseTransactions;

class MyTest extends TestCase
{
    // Inherited from base TestCase - wraps each test in transaction
}
```

**RefreshDatabase** (full reset):
```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;
    protected $seed = true;  // Seed after migrations
}
```

### Test Structure
```
tests/
├── Feature/           # Integration tests
│   ├── Controllers/   # Controller tests
│   ├── Migrations/    # Schema tests
│   └── Components/    # Blade component tests
├── Unit/              # Unit tests
│   ├── Models/        # Model tests
│   ├── Policies/      # Policy tests
│   ├── Services/      # Service tests
│   ├── Enums/         # Enum tests
│   └── Traits/        # Trait tests
└── TestCase.php       # Base test case
```

---

## CI/CD

GitHub Actions workflows in `.github/workflows/`:

| Workflow | Purpose |
|----------|---------|
| `claude.yml` | Claude Code integration for issues/PRs |
| `claude-code-review.yml` | Automated code review |
| `opencode.yml` | Additional CI checks |

Claude is triggered by mentioning `@claude` in issues or PR comments.

---

## Common Commands

```bash
# Development
php artisan serve                           # Start dev server
npm run dev                                 # Vite dev server
npm run build                               # Production build

# Database
php artisan migrate                         # Apply new migrations
php artisan migrate:fresh --seed            # Reset database with seed data
php artisan migrate:fresh --env=testing --seed  # Setup test database

# Testing
php artisan test                            # Run all tests
php artisan test --filter=MatterTest        # Run specific test
php artisan test --testsuite=Unit           # Run unit tests only

# Code Quality
npm run lint:php                            # Run Laravel Pint
npm run format:blade                        # Format Blade templates

# Artisan
php artisan make:migration add_column_name  # Create migration
php artisan make:model ModelName            # Create model
php artisan make:controller ControllerName  # Create controller
php artisan tinker                          # Interactive REPL
```

---

## Common Pitfalls

1. **Don't use `Schema::create()` for existing tables** - They exist from tier migrations

2. **Don't assume standard Laravel auth** - Users are actors with a role, `users` is a VIEW

3. **Use enums, not magic strings:**
   ```php
   // Good
   $event->code === EventCode::FILING->value

   // Bad
   $event->code === 'FIL'
   ```

4. **Use `DatabaseTransactions` for most tests** - Faster than `RefreshDatabase`

5. **CHAR columns are padded** - Use `TrimsCharColumns` trait or trim manually

6. **Translations are JSONB** - Access via Spatie translatable methods

7. **Creator/updater fields** - Use `HandlesAuditFields` trait in controllers

8. **Client users have limited access** - Check role before allowing operations

---

## Directory Structure

```
phpip/
├── app/
│   ├── Console/Commands/      # Artisan commands
│   ├── Enums/                 # PHP enums (UserRole, EventCode, etc.)
│   ├── Http/
│   │   ├── Controllers/       # Request handlers
│   │   ├── Middleware/        # HTTP middleware
│   │   └── Requests/          # Form request validation
│   ├── Models/                # Eloquent models
│   ├── Policies/              # Authorization policies
│   ├── Repositories/          # Data access layer
│   ├── Services/              # Business logic services
│   └── Traits/                # Reusable traits
├── database/
│   ├── factories/             # Model factories for testing
│   ├── migrations/            # Database migrations (tier-based)
│   ├── schema/                # Reference SQL (not used for migrations)
│   └── seeders/               # Database seeders
├── resources/
│   ├── sass/                  # SCSS stylesheets
│   └── views/                 # Blade templates
├── routes/
│   └── web.php                # Web routes
├── tests/
│   ├── Feature/               # Integration tests
│   └── Unit/                  # Unit tests
└── .github/workflows/         # CI/CD pipelines
```

---

## Form Requests

Validation is handled by Form Request classes in `app/Http/Requests/`:

```php
// Example: StoreMatterRequest, UpdateMatterRequest
// Always use form requests for validation instead of inline validation
```

---

## Repositories

Data access is abstracted through repositories in `app/Repositories/`:

- `MatterRepository` - Matter queries and filtering
- `ActorRepository` - Actor queries
- `TaskRepository` - Task queries

Use repositories for complex queries instead of putting them in models.
