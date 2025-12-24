# Issue: Fix seeder event code consistency

**Labels:** data, seeders

## Context

`TaskRulesTableSeeder` references event codes that are missing from
`EventNameTableSeeder`. This causes foreign key failures when seeding a fresh
database (no preloaded schema).

## Scope

Add missing event codes to `database/seeders/EventNameTableSeeder.php` so all
codes referenced by `TaskRulesTableSeeder` exist.

Known missing codes:
- IPER
- ORI
- REJF
- REST
- SUO
- WO (already added in local change, but not merged)

## Checklist

- [ ] Confirm current missing event codes by comparing both seeders.
- [ ] Add missing entries to `EventNameTableSeeder` with names and metadata.
- [ ] Run `php artisan db:seed --class=EventNameTableSeeder` (or full seed) on a
      fresh DB and confirm no FK violations from task rules.
- [ ] Update any related docs if needed.

