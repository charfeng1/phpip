# Issue: Phase 6B - apply common controller traits

**Labels:** refactor, phase-6b

## Context

Phase 6B is about finishing the rollout of common controller traits:
`HandlesAuditFields`, `Filterable`, and `JsonResponses`. The traits exist but
coverage is incomplete across controllers.

## Scope

Apply the traits to the remaining controllers and adjust controller code to use
trait helpers where needed.

## Checklist

- [ ] Inventory controllers missing `HandlesAuditFields`.
- [ ] Inventory controllers missing `Filterable`.
- [ ] Decide whether to introduce `JsonResponses` now and which controllers use
      it (API endpoints only).
- [ ] Apply traits and update calls to use the shared helpers.
- [ ] Run a small smoke check (controller list pages + create/update flows) or
      focused tests if available.
- [ ] Update tracking docs (e.g., `tasks/backlog/CODE_QUALITY_REPORT.md`) with
      new coverage counts.

