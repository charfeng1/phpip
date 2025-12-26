# Issues Found During E2E Testing - 2025-12-25

## Summary
| Priority | Count |
|----------|-------|
| Critical | 0 |
| High | 1 |
| Medium | 1 |
| Low | 1 |
| UX Improvement | 3 |

---

## Critical Issues
(System-breaking bugs that prevent core functionality)

*None found*

---

## High Priority Issues
(Significant bugs affecting important workflows)

### Issue H1: Export Functionality Broken
**Location**: Matter Management > Export button
**Error**:
```
Internal Server Error - TypeError
App\Repositories\MatterRepository::filter(): Argument #4 ($displayWith) must be of type string|bool, null given, called in /home/vboxuser/projects/phpip/app/Http/Controllers/MatterController.php on line 460
```

**Steps to Reproduce**:
1. Navigate to Matter Management page
2. Click "Export" button
3. Error page displayed

**Expected Behavior**: CSV file should download with matter data

**Root Cause**: The `export()` method in `MatterController` is calling `MatterRepository::filter()` without providing the required `$displayWith` parameter (or passing null).

**Files to Investigate**:
- `app/Http/Controllers/MatterController.php` (line 460)
- `app/Repositories/MatterRepository.php` (filter method)

**Suggested Fix**: Ensure the `$displayWith` parameter has a default value or is properly passed from the controller.

---

## Medium Priority Issues
(Bugs or problems that have workarounds)

### Issue M1: Client Column Shows Raw JSON Data
**Location**: Matter Management list view
**Description**: The "Client" column displays raw JSON data instead of the formatted client name.

**Current Display**:
```json
{"id":5,"actor_id":124,"display_name":"Tesla","name":"Tesla Motors Inc.","first_name":null,"email":null,"display_order":1,"role_code":"CLI","role_name":null,"shareable":true,"show_ref":false,"show_company":true,"sho...
```

**Expected Display**: "Tesla Motors Inc." or "Tesla" (the display_name)

**Steps to Reproduce**:
1. Navigate to Matter Management
2. Look at the Client column for matters with actors linked

**Impact**: Poor user experience - raw JSON is difficult to read

**Suggested Fix**: Update the Vue component or blade template to extract and display only the `display_name` or `name` field from the JSON object.

---

## Low Priority Issues
(Minor issues, cosmetic problems)

### Issue L1: Matter Count Not Updating After Filter
**Location**: Matter Management page header
**Description**: The "X cases found" count may not update correctly after applying filters.

**Steps to Reproduce**:
1. Navigate to Matter Management (shows "8 cases found")
2. Apply Ref filter with "PAT001"
3. List shows 7 matters but header might still show "8 cases found"

**Impact**: Minor - filtering still works correctly, just count display is inaccurate

---

## UX Improvements / Friction Points
(Usability suggestions, not bugs)

### UX1: No Visible Validation Feedback on Forms
**Location**: Create Matter form, and potentially other forms
**Description**: When required fields are missing, form submission is blocked but no error message is shown to the user. The form simply doesn't submit, leaving users confused.

**Current Behavior**:
- User leaves Country field empty
- Clicks "Create" button
- Nothing happens - no error, no submission

**Expected Behavior**:
- Red border around required fields
- Error message like "Country is required"
- Clear indication of what needs to be fixed

**Recommendation**: Add client-side validation feedback showing which fields are required and what's missing.

---

### UX2: Locale Detection Shows Chinese UI
**Location**: Login page, global
**Description**: On first load, the UI shows Chinese labels (e.g., "登录" instead of "Login") due to browser locale detection.

**Impact**: Confusing for non-Chinese users

**Recommendation**:
- Default to English
- Add language selector
- Remember user's language preference

---

### UX3: Create Matter Form Styling
**Location**: `/matter/create?operation=new`
**Description**: The create matter form appears very basic/unstyled compared to the rest of the application. It shows raw form elements without the modern styling seen elsewhere.

**Current Display**: Basic HTML form elements with no styling
**Expected Display**: Styled form matching the rest of the phpIP interface

**Recommendation**: Apply consistent styling to the create matter form.

---

## Notes

### Database/Seeding Observations
- The default user is `phpipuser` (not `Tesla` as documented in some places)
- Task rules seeding failed with FK constraint: `Key (rule_used)=(204) is not present in table 'task_rules'`
- This prevented full testing of task/renewal workflows

### Test Environment
- PHP 8.3.6
- Laravel 12.16.0
- PostgreSQL database
- Browser: Chrome with Claude extension
- Dev server running on port 8001 (8000 was in use)
