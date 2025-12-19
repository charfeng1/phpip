# Issue: Document PostgreSQL migration path

**Labels:** documentation, postgresql

## Context

The application now supports PostgreSQL/Supabase. Users need clear documentation on how to migrate from MySQL or set up fresh on PostgreSQL.

## Documentation Needed

### 1. Fresh PostgreSQL Setup
- Prerequisites (PostgreSQL 15+, extensions)
- Environment configuration (.env)
- Running migrations
- Seeding initial data

### 2. MySQL to PostgreSQL Migration
- Export existing MySQL data
- Data type mapping considerations
- Running migration scripts
- Verification steps

### 3. Supabase-Specific Setup
- Creating Supabase project
- Connection string configuration
- Connection pooler settings (port 6543 vs 5432)
- Enabling required extensions (fuzzystrmatch, etc.)
- Storage setup (if using Supabase Storage)

### 4. Database Differences Reference
| Feature | MySQL | PostgreSQL |
|---------|-------|------------|
| JSON access | JSON_EXTRACT() | ->> operator |
| Case-insensitive | LIKE + COLLATE | ILIKE |
| String concat | GROUP_CONCAT | STRING_AGG |
| Null handling | IFNULL | COALESCE |
| Boolean | TINYINT(1) | BOOLEAN |

## Location
- `docs/database-setup.md` or
- `README.md` section

## Priority
Medium - needed before PostgreSQL is used in production
