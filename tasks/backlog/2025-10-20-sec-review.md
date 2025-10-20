# Security Remediation Tasks (2025-10-20)

## High Severity

1. **Escaped Output in Blade Templates**
   - `resources/views/email/renewalCall.blade.php`
     - Replace `{!! $template->body !!}` and `{!! $ren['desc'] !!}` with safely filtered/escaped content. Consider using `{{ }}` after sanitizing HTML (e.g., `strip_tags`, `Purifier`) or whitelist safe markup.
   - `resources/views/matter/index.blade.php`
     - Audit conditional attributes that concatenate user-controlled data; replace with `@class`, `@checked`, `@selected` helpers or sanitized escapes.

2. **SQL Injection Risk via `DB::raw`**
   - Review `DB::raw` usage in `app/Models/Matter.php` and `app/Http/Controllers/RenewalController.php`.
   - Replace string concatenation with parameter binding (`selectRaw` with placeholders) or query builder expressions.
   - Add tests covering typical payloads to verify sanitization.

## Medium Severity

3. **Authorization Hardening**
   - Update `MatterPolicy` to default-deny and explicitly whitelist allowed roles.
   - Implement missing policies for critical models (User, Actor, Task, Renewal) and enforce them in controllers/routes.
   - Standardize authorization calls (`$this->authorize`) across all CRUD operations.
   - Document required abilities (view, update, delete) for each role.
