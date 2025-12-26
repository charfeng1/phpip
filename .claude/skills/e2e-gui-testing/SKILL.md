---
name: e2e-gui-testing
description: phpIP E2E GUI testing workflow using Claude for Chrome. Use when (1) starting a new E2E test session for phpIP, (2) validating phpIP UI workflows after changes, (3) documenting test results and bugs found, or (4) creating comprehensive test run reports. Covers matter lifecycle, actor management, events, tasks, renewals, and IP family relationships.
---

# phpIP E2E GUI Testing

End-to-end GUI testing workflow for phpIP using Claude for Chrome browser automation.

## Quick Start

```bash
# 1. Create testing branch
git checkout -b test/e2e-gui-validation

# 2. Start dev server
php artisan serve

# 3. Seed database if fresh
php artisan migrate --force && php artisan db:seed --force
```

**Login credentials**: `phpipuser` / `password` (DBA role)

## Test Run Setup

Create folder structure for each test session:

```
runs/e2e-gui-YYYY-MM-DD/
├── README.md        # Session overview and summary
├── test-results.md  # Detailed test cases
└── issues.md        # Bugs and UX issues found
```

## Test Execution Workflow

### Browser Setup
1. Call `tabs_context_mcp` to get available tabs
2. Call `tabs_create_mcp` for a new tab
3. Navigate to `http://127.0.0.1:8000`

### For Each Test Category
1. Take screenshot before starting
2. Execute test steps using:
   - `read_page` - Get page elements
   - `find` - Locate specific elements
   - `form_input` - Fill form fields
   - `computer` - Click, type, scroll actions
3. Verify actual matches expected
4. Document result immediately (PASS/FAIL/BLOCKED)
5. Record any issues found

### Test Categories
See `references/phpip-test-categories.md` for detailed test cases:
1. Authentication & Authorization
2. Dashboard Validation
3. Matter Lifecycle (CRUD)
4. Event Management
5. Task/Renewal Workflows
6. Actor Management
7. Matter Family & Relationships
8. Search & Filtering
9. Export Functionality
10. Edge Cases & Error Handling

## Documentation Formats

### README.md Template
```markdown
# E2E GUI Testing Session - YYYY-MM-DD

## Test Environment
- **Date**: [date]
- **Branch**: test/e2e-gui-validation
- **Laravel Version**: [version]
- **Testing Tool**: Claude for Chrome

## Login Credentials
- Primary: phpipuser / password (DBA role)

## Results Summary
| Category | Passed | Failed | Blocked | Total |
|----------|--------|--------|---------|-------|
| Total | **X** | **Y** | **Z** | **N** |

### Pass Rate: X% (passed/total)

## Key Findings
### Bugs Found
1. [severity] - [description]

### UX Issues
1. [description]
```

### Test Case Template
```markdown
### Test X.Y: [Test Name]
**Status**: PASS | FAIL | BLOCKED
**Steps**:
1. [action]
2. [action]

**Expected**: [what should happen]
**Actual**: [what actually happened]
**Issues**: [any problems or None]
```

### Issue Template
```markdown
### Issue [ID]: [Title]
**Priority**: Critical | High | Medium | Low | UX
**Location**: [page/feature]
**Description**: [what's wrong]

**Steps to Reproduce**:
1. [step]

**Expected**: [correct behavior]
**Actual**: [broken behavior]
**Suggested Fix**: [recommendation]
```

## Issue Severity Levels

| Level | Definition |
|-------|-----------|
| Critical | System-breaking, blocks core functionality |
| High | Significant bugs affecting important workflows |
| Medium | Has workarounds available |
| Low | Cosmetic or minor issues |
| UX | Usability improvement suggestions |

## Test Status Values

- **PASS**: Behavior matches expected result
- **FAIL**: Actual result differs from expected
- **BLOCKED**: Cannot complete due to missing data, dependencies, or unrelated bugs

## Finalizing

1. Update test-results.md with all test outcomes
2. Document all issues in issues.md with severity
3. Update README.md with summary counts
4. Commit with descriptive message:

```bash
git add runs/e2e-gui-YYYY-MM-DD/
git commit -m "docs: Add E2E GUI test results for YYYY-MM-DD

Test Results Summary:
- Total: N tests (X passed, Y failed, Z blocked)
- Pass Rate: X%

Key Findings:
- [HIGH] [bug description]
- [MEDIUM] [bug description]
"
```
