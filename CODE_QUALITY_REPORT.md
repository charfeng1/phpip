# Code Quality Analysis Report

**Date:** December 20, 2025
**Codebase:** phpIP (Laravel 12 IP Rights Management System)
**Analyzed Files:** 27 controllers, 24 models, 5 services, 69 views

---

## Executive Summary

This report identifies areas for improvement across 6 key dimensions:
1. **Code Duplication** - 60+ repeated CRUD patterns, 85 authorization checks
2. **Modularization** - 2 controllers over 900 lines, 1 model with 1241 lines
3. **Conditional Complexity** - 7 large switch statements (30+ cases)
4. **Hardcoded Values** - 150+ magic strings for roles, events, categories
5. **Boilerplate Reduction** - 46 repeated auth tracking, 72 inconsistent JSON responses
6. **Style/Consistency** - Mixed patterns between controllers

---

## 1. Code Duplication

### 1.1 CRUD Controller Patterns (HIGH PRIORITY)

**Problem:** Nearly identical store/update/destroy methods across 20+ controllers

| Pattern | Occurrences | Files |
|---------|-------------|-------|
| `$request->merge(['creator' => Auth::user()->login])` | 14 | CategoryController, RoleController, EventNameController, etc. |
| `$request->merge(['updater' => Auth::user()->login])` | 16 | Same controllers |
| `$request->validate([...])` inline | 46 | All controllers |

**Key Files:**
- `app/Http/Controllers/CategoryController.php:69-81` (store)
- `app/Http/Controllers/RoleController.php:66-76` (store)
- `app/Http/Controllers/ClassifierTypeController.php:65-74` (store)
- `app/Http/Controllers/MatterTypeController.php:65-74` (store)
- `app/Http/Controllers/EventNameController.php:70-83` (store)

**Recommendation:** Create a `CrudOperations` trait or base `ResourceController`:
```php
trait CrudOperations {
    public function store(Request $request) {
        $this->authorize('create', $this->modelClass);
        $request->validate($this->storeRules());
        return $this->modelClass::create(
            $request->mergeIfMissing(['creator' => Auth::user()->login])
                ->except(['_token', '_method'])
        );
    }
}
```

### 1.2 Index Filtering Logic (MEDIUM PRIORITY)

**Problem:** Similar filtering patterns repeated in 17+ controllers

**Files:**
- `app/Http/Controllers/CategoryController.php:23-46`
- `app/Http/Controllers/RoleController.php:24-45`
- `app/Http/Controllers/ClassifierTypeController.php:23-44`
- `app/Http/Controllers/EventNameController.php:24-47`
- `app/Http/Controllers/RuleController.php:25-93`

**Pattern:**
```php
if (! is_null($Code)) {
    $model = $model->whereLike('code', $Code.'%');
}
if (! is_null($Name)) {
    $model = $model->whereJsonLike('name', $Name);
}
```

**Recommendation:** Create a `Filterable` trait:
```php
trait Filterable {
    protected array $filterRules = [];

    public function applyFilters($query, Request $request): Builder {
        foreach ($this->filterRules as $key => $callback) {
            if ($request->filled($key)) {
                $query = $callback($query, $request->input($key));
            }
        }
        return $query;
    }
}
```

### 1.3 View Template Duplication (MEDIUM PRIORITY)

**Problem:** Index table layouts repeated across 8+ views

**Files:**
- `resources/views/category/index.blade.php:4-46`
- `resources/views/role/index.blade.php:4-67`
- `resources/views/classifier_type/index.blade.php:4-67`
- `resources/views/eventname/index.blade.php:4-72`

**Recommendation:** Create Blade components:
```blade
<x-list-with-panel
    :items="$items"
    :columns="['code' => 'Code', 'name' => 'Name']"
    :filters="['Code', 'Name']"
    resource="category"
/>
```

---

## 2. Modularization Issues

### 2.1 Fat Controllers (CRITICAL)

#### RenewalController.php - 1308 lines
**Location:** `app/Http/Controllers/RenewalController.php`

**Issues:**
- Fee calculation logic in private methods (lines 224-284)
- Email preparation logic (lines 294-537)
- Direct CURL calls for Dolibarr API (lines 781-830)
- Direct database inserts (lines 166, 208, 313, 552)

**Recommendation:** Extract to services:
```
app/Services/
├── RenewalWorkflowService.php     # State transitions
├── RenewalFeeCalculationService.php # Fee logic
├── RenewalNotificationService.php  # Email handling
├── DolibarrInvoiceService.php      # API integration
└── PaymentOrderXMLService.php      # XML generation
```

#### MatterController.php - 921 lines
**Location:** `app/Http/Controllers/MatterController.php`

**Issues:**
- `storeFamily()` method spans 305 lines (383-687)
- Complex logic mixing OPS API, matter creation, events, actors

**Recommendation:** Extract `PatentFamilyCreationService`:
```php
class PatentFamilyCreationService {
    public function createFromOPS(string $docnum, array $params): Collection;
    private function createMatter(array $opsData): Matter;
    private function attachActors(Matter $matter, array $actors): void;
    private function createEvents(Matter $matter, array $procedure): void;
}
```

### 2.2 God Model (CRITICAL)

#### Matter.php - 1241 lines
**Location:** `app/Models/Matter.php`

**Issues:**
- 20+ actor relationship methods (lines 103-600)
- 362-line `filter()` method (lines 669-1030)
- Description generation logic (lines 1080-1157)
- Address calculation (lines 1169-1189)

**Recommendation:**
1. Extract to `MatterRepository`:
```php
class MatterRepository {
    public function filter(MatterFilterRequest $request): Builder;
    public function findWithRelations(int $id): Matter;
}
```

2. Use traits for organization:
```php
trait HasActorRelationships {
    public function client() { ... }
    public function applicants() { ... }
    // etc.
}
```

3. Create `MatterPresenter`:
```php
class MatterPresenter {
    public function getDescription(Matter $matter, string $lang): array;
    public function getBillingAddress(Matter $matter): string;
}
```

---

## 3. Conditional Complexity

### 3.1 Large Switch Statements

| Location | Lines | Cases | Description |
|----------|-------|-------|-------------|
| `Matter.php:883-982` | 100 | 30+ | Dynamic query filtering |
| `RenewalController.php:60-105` | 45 | 13 | Renewal filtering |
| `RenewalController.php:1269-1300` | 31 | 6 | Log filtering |
| `DocumentController.php:154-194` | 40 | 10 | Document filtering |
| `MatterController.php:258-313` | 55 | 3 | Operation type handling |
| `MatterController.php:628-679` | 51 | 4 | Procedure step handling |
| `Event.php:209-238` | 29 | 5 | Country-based URL generation |

**Recommendation:** Use Strategy Pattern or lookup maps:

```php
// Before (Matter.php:883-982)
switch ($key) {
    case 'Ref': $query->where(...); break;
    case 'Cat': $query->where(...); break;
    // 28 more cases...
}

// After - Lookup Map
private array $filterMap = [
    'Ref' => fn($q, $v) => $q->where('caseref', 'LIKE', "$v%"),
    'Cat' => fn($q, $v) => $q->whereLike('category_code', "$v%"),
    // etc.
];

foreach ($filters as $key => $value) {
    if (isset($this->filterMap[$key])) {
        $query = $this->filterMap[$key]($query, $value);
    }
}
```

### 3.2 Country-Based URL Generation
**Location:** `app/Models/Event.php:209-238`

**Recommendation:** Create strategy classes:
```php
interface PatentOfficeUrlGenerator {
    public function generateUrl(Event $event): ?string;
}

class EpoUrlGenerator implements PatentOfficeUrlGenerator { ... }
class InpiUrlGenerator implements PatentOfficeUrlGenerator { ... }
class UsUrlGenerator implements PatentOfficeUrlGenerator { ... }
```

---

## 4. Hardcoded Values

### 4.1 Role Codes (HIGH PRIORITY)

**Values:** `'DBA'`, `'DBRW'`, `'DBRO'`, `'CLI'`

**Occurrences:** 50+ across 12 files

**Files:**
- `app/Providers/AppServiceProvider.php:29-33`
- `app/Policies/*.php` (all 7 policy files)
- `app/Models/Matter.php:212, 735, 742, 864`
- `app/Models/Task.php:193`

**Recommendation:** Create `UserRole` enum:
```php
enum UserRole: string {
    case ADMIN = 'DBA';
    case READ_WRITE = 'DBRW';
    case READ_ONLY = 'DBRO';
    case CLIENT = 'CLI';

    public static function internalRoles(): array {
        return [self::ADMIN, self::READ_WRITE, self::READ_ONLY];
    }
}
```

### 4.2 Actor Role Codes (HIGH PRIORITY)

**Values:** `'CLI'`, `'APP'`, `'OWN'`, `'AGT'`, `'INV'`, `'DEL'`, `'CNT'`, `'PAY'`

**Occurrences:** 40+ in Matter.php and controllers

**Recommendation:** Create `ActorRole` enum:
```php
enum ActorRole: string {
    case CLIENT = 'CLI';
    case APPLICANT = 'APP';
    case OWNER = 'OWN';
    case AGENT = 'AGT';
    case INVENTOR = 'INV';
    case DELEGATE = 'DEL';
    case CONTACT = 'CNT';
    case PAYOR = 'PAY';
}
```

### 4.3 Event Codes (HIGH PRIORITY)

**Values:** `'FIL'`, `'PUB'`, `'GRT'`, `'REG'`, `'PRI'`, `'ENT'`, `'PFIL'`, `'REN'`, `'PR'`

**Occurrences:** 60+ across models, controllers, services

**Files:**
- `app/Models/Event.php:190, 202, 208`
- `app/Models/Matter.php:410-506`
- `app/Models/Task.php:338-369`
- `app/Http/Controllers/MatterController.php` (20+ occurrences)
- `app/Services/DocumentMergeService.php:73-102`

**Recommendation:** Create `EventCode` enum:
```php
enum EventCode: string {
    case FILING = 'FIL';
    case PUBLICATION = 'PUB';
    case GRANT = 'GRT';
    case REGISTRATION = 'REG';
    case PRIORITY = 'PRI';
    case ENTRY = 'ENT';
    case PCT_FILING = 'PFIL';
    case RENEWAL = 'REN';
    case PRIORITY_CLAIM = 'PR';
}
```

### 4.4 Magic Numbers (MEDIUM PRIORITY)

| Value | Usage | Files |
|-------|-------|-------|
| `21` | Pagination | ActorController, UserController, CountryController, RuleController |
| `15`, `10` | Autocomplete limit | AutocompleteController (10 occurrences) |
| `50` | Audit log pagination | AuditLogController |
| `25`, `18` | Matter/Task pagination | MatterController, TaskController |

**Recommendation:** Create `config/pagination.php`:
```php
return [
    'default' => 21,
    'autocomplete' => 15,
    'audit_log' => 50,
    'matters' => 25,
    'tasks' => 18,
];
```

### 4.5 External API URLs (HIGH PRIORITY)

**Location:** `app/Models/Event.php:207-236`

**Hardcoded URLs:**
- `https://ops.epo.org/3.2`
- `https://register.epo.org/espacenet/application`
- `https://data.inpi.fr/brevets/`
- `https://data.inpi.fr/marques/`
- `http://www.ipo.gov.uk/p-ipsum/Case/ApplicationNumber/`
- `https://euipo.europa.eu/eSearch/#details/trademarks/`

**Recommendation:** Move to `config/services.php`:
```php
'patent_offices' => [
    'EP' => 'https://register.epo.org/espacenet/application',
    'FR_PAT' => 'https://data.inpi.fr/brevets/',
    'FR_TM' => 'https://data.inpi.fr/marques/',
    'GB' => 'http://www.ipo.gov.uk/p-ipsum/Case/ApplicationNumber/',
    'EM' => 'https://euipo.europa.eu/eSearch/#details/trademarks/',
],
```

---

## 5. Boilerplate Reduction

### 5.1 Authorization Checks (HIGH PRIORITY)

**Problem:** 85 manual authorization checks across 13 controllers

**Files with most occurrences:**
- `RenewalController.php` - 16 instances
- `UserController.php` - 7 instances
- `CategoryController.php` - 6 instances
- `ActorController.php` - 6 instances

**Recommendation:** Create authorization middleware:
```php
class AuthorizeResourceAction {
    public function handle(Request $request, Closure $next, string $model) {
        $action = Route::currentRouteAction();
        $ability = match($action) {
            'index' => 'viewAny',
            'create', 'store' => 'create',
            'show', 'edit' => 'view',
            'update' => 'update',
            'destroy' => 'delete',
            default => null,
        };

        if ($ability) {
            Gate::authorize($ability, $model);
        }

        return $next($request);
    }
}
```

### 5.2 Request Validation (HIGH PRIORITY)

**Problem:** Only 2 Form Request classes exist, but 46 inline validations

**Current:** `app/Http/Requests/` contains only:
- `MatterExportRequest.php`
- `StoreActorPivotRequest.php`

**Recommendation:** Create Form Requests for each entity:
```
app/Http/Requests/
├── Actor/
│   ├── StoreActorRequest.php
│   └── UpdateActorRequest.php
├── Category/
│   ├── StoreCategoryRequest.php
│   └── UpdateCategoryRequest.php
├── User/
│   ├── StoreUserRequest.php
│   └── UpdateUserRequest.php
└── ...
```

### 5.3 JSON Response Formatting (MEDIUM PRIORITY)

**Problem:** 72 occurrences of `response()->json()` with inconsistent formats

**Current formats (mixed):**
- `['success' => message]`
- `['error' => message]`
- `['redirect' => route]`
- `['status' => 'success', 'message' => ...]`

**Recommendation:** Create response trait:
```php
trait JsonResponses {
    protected function successResponse($data = null, string $message = 'Success', int $code = 200) {
        return response()->json([
            'status' => 'success',
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function errorResponse(string $message = 'Error', int $code = 400, $errors = null) {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
```

### 5.4 Table Comments Loading (LOW PRIORITY)

**Problem:** 12 controllers load table comments identically

**Pattern:**
```php
$table = new ModelName;
$tableComments = $table->getTableComments();
return view('...', compact('tableComments'));
```

**Recommendation:** Create View Composer:
```php
View::composer(['*.create', '*.show'], function ($view) {
    $model = $this->resolveModelFromView($view);
    $view->with('tableComments', (new $model)->getTableComments());
});
```

---

## 6. Recommended Enums to Create

```php
// app/Enums/UserRole.php
enum UserRole: string {
    case ADMIN = 'DBA';
    case READ_WRITE = 'DBRW';
    case READ_ONLY = 'DBRO';
    case CLIENT = 'CLI';
}

// app/Enums/ActorRole.php
enum ActorRole: string {
    case CLIENT = 'CLI';
    case APPLICANT = 'APP';
    case OWNER = 'OWN';
    case AGENT = 'AGT';
    case INVENTOR = 'INV';
    case DELEGATE = 'DEL';
    case CONTACT = 'CNT';
    case PAYOR = 'PAY';
}

// app/Enums/EventCode.php
enum EventCode: string {
    case FILING = 'FIL';
    case PUBLICATION = 'PUB';
    case GRANT = 'GRT';
    case REGISTRATION = 'REG';
    case PRIORITY = 'PRI';
    case ENTRY = 'ENT';
    case PCT_FILING = 'PFIL';
    case RENEWAL = 'REN';
    case PRIORITY_CLAIM = 'PR';
}

// app/Enums/CategoryCode.php
enum CategoryCode: string {
    case PATENT = 'PAT';
    case TRADEMARK = 'TM';
}

// app/Enums/RenewalStep.php
enum RenewalStep: string {
    case FIRST_CALL = 'firstcall';
    case LAST_CALL = 'lastcall';
    case WARNING_CALL = 'warncall';
}
```

---

## 7. Implementation Roadmap

### Phase 1: Quick Wins (1-2 weeks effort)
1. Create PHP 8.1 Enums for roles, event codes, category codes
2. Create `config/pagination.php` for magic numbers
3. Move API URLs to `config/services.php`
4. Create `JsonResponses` trait for consistent responses

### Phase 2: Boilerplate Reduction (2-3 weeks effort)
1. Create Form Request classes for validation
2. Create `CrudOperations` trait for common CRUD
3. Create authorization middleware
4. Implement audit field handling via model events

### Phase 3: Service Extraction (3-4 weeks effort)
1. Extract `RenewalWorkflowService` from RenewalController
2. Extract `DolibarrInvoiceService` for API calls
3. Extract `PatentFamilyCreationService` from MatterController
4. Create `RenewalFeeCalculationService`

### Phase 4: Repository Pattern (2-3 weeks effort)
1. Create `MatterRepository` with filter logic
2. Create `TaskRepository` for renewal queries
3. Create `ActorRepository` for actor lookups
4. Move complex queries from models to repositories

### Phase 5: View Components (1-2 weeks effort)
1. Create `ListWithPanel` Blade component
2. Create `AutocompleteField` component
3. Create `FormGenerator` component
4. Create view composers for common data

---

## 8. Priority Matrix

| Issue | Severity | Effort | Impact | Priority |
|-------|----------|--------|--------|----------|
| Hardcoded role strings | High | Low | High | **P1** |
| Fat controllers (>900 lines) | Critical | High | High | **P1** |
| Missing Form Requests | High | Medium | High | **P1** |
| God model (Matter.php) | Critical | High | High | **P2** |
| Repeated CRUD patterns | High | Medium | Medium | **P2** |
| Switch statement complexity | Medium | Medium | Medium | **P2** |
| Inconsistent JSON responses | Medium | Low | Medium | **P3** |
| View template duplication | Medium | Medium | Low | **P3** |
| Magic numbers | Low | Low | Low | **P4** |

---

## Summary Statistics

| Metric | Count |
|--------|-------|
| Files with duplication issues | 22 controllers |
| Hardcoded magic strings | 150+ |
| Large switch statements (>10 cases) | 7 |
| Lines in largest controller | 1,308 (RenewalController) |
| Lines in largest model | 1,241 (Matter.php) |
| Inline validation calls | 46 |
| Missing Form Requests | 44 estimated |
| Authorization check duplications | 85 |

This analysis provides a roadmap for improving code quality and maintainability. Implementation should be phased to minimize disruption while maximizing long-term benefits.
