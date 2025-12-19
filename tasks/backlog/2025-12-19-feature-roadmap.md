# Feature Roadmap

## Overview
Key features needed to make phpIP production-ready for patent firms.

---

## High Priority

### 1. Team-Based Privileges with Nested Hierarchy
**Status:** Not started
**Effort:** 1-2 days

Implement hierarchical team structure where leaders see their team's work.

**Requirements:**
- Use existing `users.parent_id` field for supervisor relationships
- Support nested hierarchy (Partner → Senior → Junior)
- No ethical walls between teams (all partners can see all work if needed)
- Leaders see all matters/tasks for their direct and indirect reports
- Individual users see only their assigned work

**Implementation:**
- [ ] Update `MatterPolicy` with recursive team checking
- [ ] Add global scope to `Matter` model for team filtering
- [ ] Add global scope to `Task` model for team filtering
- [ ] Create `TeamService` for hierarchy traversal (recursive CTE or collection-based)
- [ ] Add "My Team" filter option to dashboard and matter list
- [ ] Update task list to show team members' tasks for leaders
- [ ] Add UI to manage user hierarchy (assign parent_id)

**Example hierarchy:**
```
Partner A (sees all under them)
  └── Senior Associate (sees their work + juniors)
        ├── Junior 1 (sees only assigned)
        └── Junior 2 (sees only assigned)
```

---

### 2. Complete Authorization Policies
**Status:** Not started
**Effort:** 2-3 days

Currently only 4 policies exist: Matter, Actor, Task, User. Need policies for all models.

**Models needing policies:**
- [ ] `FeePolicy` - who can view/edit billing
- [ ] `RenewalPolicy` - who can mark renewals paid
- [ ] `RulePolicy` - who can modify deadline calculation rules
- [ ] `EventPolicy` - who can add/edit events
- [ ] `ClassifierPolicy` - who can manage classifications
- [ ] `EventNamePolicy` - who can modify event types
- [ ] `CategoryPolicy` - who can modify matter categories
- [ ] `CountryPolicy` - who can modify country settings
- [ ] `TemplatePolicy` - who can edit email templates

**Implementation:**
- [ ] Create policy classes for each model
- [ ] Register policies in `AuthServiceProvider`
- [ ] Add `$this->authorize()` calls in all controller methods
- [ ] Default-deny approach: explicitly whitelist allowed actions per role
- [ ] Document role-permission matrix

---

### 3. Audit Trail
**Status:** Not started
**Effort:** 2-3 days

Track all data changes with who/what/when for compliance and dispute resolution.

**Requirements:**
- Log all create/update/delete operations
- Store: user, model, field, old value, new value, timestamp
- Viewable audit history per record
- Searchable audit log for admins

**Implementation options:**
- [ ] Option A: Use `spatie/laravel-activitylog` package (recommended)
- [ ] Option B: Custom trait with model events

**Scope:**
- [ ] Matter changes
- [ ] Task changes (especially done_date)
- [ ] Event changes (dates are critical)
- [ ] Fee changes
- [ ] Actor relationship changes
- [ ] User permission changes

---

## Medium Priority

### 4. Test Coverage
**Status:** ~5% coverage
**Effort:** 3-5 days

Expand test suite for critical paths.

**Priority test areas:**
- [ ] Authentication (login, logout, password reset)
- [ ] Matter CRUD operations
- [ ] Task creation via rules
- [ ] Renewal workflow (call → invoice → payment)
- [ ] Authorization policies
- [ ] API endpoints

**Implementation:**
- [ ] Configure SQLite/PostgreSQL for testing
- [ ] Add feature tests for auth flows
- [ ] Add feature tests for matter operations
- [ ] Add unit tests for services (DocumentMergeService, OPSService)
- [ ] Add policy tests for authorization
- [ ] Set up CI to run tests on push

---

### 5. Client Portal
**Status:** Not started
**Effort:** 3-5 days

Self-service portal for clients to view their portfolio status.

**Features:**
- [ ] Read-only dashboard for CLI role users
- [ ] View their matters and current status
- [ ] View upcoming deadlines/renewals
- [ ] View fee history
- [ ] Download/export portfolio summary
- [ ] Optional: request actions (triggers notification to staff)

**Implementation:**
- [ ] Create dedicated client views (simpler than staff UI)
- [ ] Ensure all queries respect CLI role scoping
- [ ] Add client-specific dashboard route
- [ ] Email notification when matter status changes

---

### 6. Bulk Operations
**Status:** Not started
**Effort:** 2-3 days

Batch processing for common operations.

**Features:**
- [ ] Bulk task completion (select multiple, mark done)
- [ ] Bulk matter status update
- [ ] Bulk reassignment (change responsible user)
- [ ] Bulk renewal processing
- [ ] Bulk export (selected matters to CSV)

**Implementation:**
- [ ] Add checkbox selection to list views
- [ ] Create batch action endpoints
- [ ] Add confirmation modals for destructive actions
- [ ] Progress indicator for large batches
- [ ] Audit log entries for bulk changes

---

### 7. Reporting & Analytics
**Status:** Not started
**Effort:** 3-5 days

Portfolio insights and business intelligence.

**Reports needed:**
- [ ] Portfolio summary by client/category/country
- [ ] Deadline aging report (overdue, due this week/month)
- [ ] Renewal forecast (upcoming renewals with fees)
- [ ] Workload distribution by user
- [ ] Matter lifecycle timeline
- [ ] Fee summary by client (for invoicing)

**Implementation:**
- [ ] Create dedicated reports controller
- [ ] Add report views with filters
- [ ] Chart.js or similar for visualizations
- [ ] PDF export option
- [ ] Scheduled report emails (optional)

---

## Future Considerations

### 8. Document Storage
- Store patent application XML/documents in Supabase Storage
- Link documents to matters
- Version history

### 9. API Authentication
- Token-based API auth for integrations
- OpenAPI/Swagger documentation
- Rate limiting

### 10. Time Tracking
- Log billable hours per matter
- Integration with invoicing
