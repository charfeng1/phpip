# Hardening Roadmap

## Overview
The codebase needs structural and operational improvements before SaaS launch. This epic groups the key workstreams.

## Subtasks
1. **Introduce Automated Testing & Static Analysis**
   - Add Pest/PHPUnit feature tests for critical flows (auth, matter CRUD, renewals).
   - Configure Larastan/PHPStan and Psalm/Lint in CI.
   - Add GitHub Actions (or equivalent) to run tests on every push.

2. **Refactor Controllers into Application Services**
   - Extract complex logic from `MatterController`, `RenewalController`, etc., into service classes or domain actions.
   - Implement DTO/ViewModels for Blade views to reduce inline business logic.

3. **Blade Component Library**
   - Convert repeated UI widgets (cards, dropdowns, alerts, modals) into Blade components or Alpine components.
   - Add accessibility attributes & ARIA roles.

4. **Multi-tenant Readiness**
   - Decide on tenancy pattern (per-tenant schema vs. scoped tables).
   - Implement tenant scoping in queries, middleware to set active tenant, tenant seeding.
   - Audit storage paths, caches, queues for tenant isolation.

5. **CI/CD & Deployment Automation**
   - Provision staging & production via Forge/Vapor or Terraform scripts.
   - Automate `composer install`, `npm run build`, migrations, cache clears.
   - Document rollback procedure. 

6. **Observability & Ops**
   - Integrate centralized logging, error tracking, uptime monitoring.
   - Configure backups (DB, storage) with restore drills.

7. **Documentation & Runbooks**
   - Write admin/user docs, API references.
   - Draft incident response plan, change-log process, and support escalation.
