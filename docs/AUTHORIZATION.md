# Authorization Policies

This document describes the role-based access control (RBAC) system implemented in phpIP.

## System Roles

| Role | Code | Description |
|------|------|-------------|
| Database Administrator | `DBA` | Full access to all features and data |
| Database Read/Write | `DBRW` | Standard internal staff with read/write access |
| Database Read-Only | `DBRO` | Internal staff with read-only access |
| Client | `CLI` | External client with access limited to their own matters |

## Role-Permission Matrix

### Matter-Related Models

| Model | Policy | DBA | DBRW | DBRO | CLI |
|-------|--------|-----|------|------|-----|
| Matter | MatterPolicy | Full | Full | Read | Own matters only |
| Actor | ActorPolicy | Full | Full | Read | No |
| Task | TaskPolicy | Full | Full | Read | Own matters only |
| Event | EventPolicy | Full | Full | Read | Own matters only |
| Classifier | ClassifierPolicy | Full | Full | Read | Own matters only |

### Billing & Renewals

| Model | Policy | DBA | DBRW | DBRO | CLI |
|-------|--------|-----|------|------|-----|
| Fee | FeePolicy | Full | Read | Read | No |
| RenewalsLog | RenewalPolicy | Full | Full | Read | Own matters only |

### System Configuration

| Model | Policy | DBA | DBRW | DBRO | CLI |
|-------|--------|-----|------|------|-----|
| Rule | RulePolicy | Full | Read | Read | No |
| EventName | EventNamePolicy | Full | Read | Read | No |
| Category | CategoryPolicy | Full | Read | Read | No |
| Country | CountryPolicy | Full | Read | Read | No |
| TemplateClass | TemplateClassPolicy | Full | Read | Read | No |
| TemplateMember | TemplateMemberPolicy | Full | Read | Read | No |

### User Management

| Model | Policy | DBA | DBRW | DBRO | CLI |
|-------|--------|-----|------|------|-----|
| User | UserPolicy | Full | Own profile | Own profile | Own profile |

## Permission Types

- **Full**: Can view, create, update, and delete records
- **Read**: Can only view records
- **Own matters only**: Can view records associated with matters where they are the client
- **Own profile**: Can view and update their own user profile
- **No**: No access

## Default-Deny Approach

All policies use a default-deny approach:
- Actions not explicitly allowed are denied
- Roles must be explicitly whitelisted for each action
- Empty or null roles are treated as client (CLI) access

## Gate Definitions

In addition to policies, the following gates are defined in `AppServiceProvider`:

```php
Gate::define('client', fn ($user) => $user->default_role === 'CLI' || empty($user->default_role));
Gate::define('except_client', fn ($user) => $user->default_role !== 'CLI' && !empty($user->default_role));
Gate::define('admin', fn ($user) => $user->default_role === 'DBA');
Gate::define('readwrite', fn ($user) => in_array($user->default_role, ['DBA', 'DBRW']));
Gate::define('readonly', fn ($user) => in_array($user->default_role, ['DBA', 'DBRW', 'DBRO']));
```

## Policy Registration

All policies are registered in `App\Providers\AuthServiceProvider`:

```php
protected $policies = [
    Matter::class => MatterPolicy::class,
    Actor::class => ActorPolicy::class,
    Task::class => TaskPolicy::class,
    User::class => UserPolicy::class,
    Fee::class => FeePolicy::class,
    RenewalsLog::class => RenewalPolicy::class,
    Rule::class => RulePolicy::class,
    Event::class => EventPolicy::class,
    Classifier::class => ClassifierPolicy::class,
    EventName::class => EventNamePolicy::class,
    Category::class => CategoryPolicy::class,
    Country::class => CountryPolicy::class,
    TemplateClass::class => TemplateClassPolicy::class,
    TemplateMember::class => TemplateMemberPolicy::class,
];
```

## Controller Authorization

Controllers use `$this->authorize()` calls to enforce policy checks:

```php
// Check if user can view any records
$this->authorize('viewAny', Model::class);

// Check if user can view a specific record
$this->authorize('view', $model);

// Check if user can create records
$this->authorize('create', Model::class);

// Check if user can update a specific record
$this->authorize('update', $model);

// Check if user can delete a specific record
$this->authorize('delete', $model);
```

## Client Access Pattern

For models related to matters (Event, Classifier, RenewalsLog), client access is determined by:

1. Loading the associated matter
2. Checking if the client actor on that matter matches the current user

```php
if ($user->default_role === 'CLI' || empty($user->default_role)) {
    $matter = $model->matter()->first();
    if ($matter instanceof Matter) {
        $clientActor = optional($matter->clientFromLnk())->actor_id;
        return (int) $clientActor === (int) $user->id;
    }
}
```
