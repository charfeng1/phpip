# Supabase Migration Guide

This guide provides instructions for migrating the phpIP application from MySQL to Supabase (PostgreSQL).

## Overview

The application has been updated to use PostgreSQL instead of MySQL. All MySQL-specific SQL syntax in the application code has been converted to PostgreSQL syntax.

**IMPORTANT**: This migration involves significant database schema work including:
- 21 tables to create
- 6 database views to convert
- 15 database triggers to convert
- 2 stored procedures to convert
- 1 stored function to convert

For complete details on all database objects, see [DATABASE_OBJECTS.md](DATABASE_OBJECTS.md).

## Prerequisites

1. A Supabase account (sign up at https://supabase.com)
2. A new Supabase project created
3. PHP 8.2+ with PostgreSQL PDO extension (`pdo_pgsql`)
4. Supabase project with the `fuzzystrmatch` extension enabled (needed for phonetic actor matching)

## Step 1: Install PostgreSQL PHP Extension

Ensure you have the PostgreSQL PDO extension installed:

```bash
# Ubuntu/Debian
sudo apt-get install php8.2-pgsql

# macOS (via Homebrew)
brew install php@8.2
pecl install pdo_pgsql

# Verify installation
php -m | grep pdo_pgsql
```

## Step 2: Set Up Supabase Project

1. Log in to your Supabase dashboard at https://app.supabase.com
2. Create a new project or use an existing one
3. Navigate to **Settings** → **Database**
4. Note down your connection details:
   - **Host**: `db.your-project-ref.supabase.co`
   - **Port**: `5432`
   - **Database name**: `postgres`
   - **User**: `postgres`
   - **Password**: Your database password

## Step 2a: Enable Required PostgreSQL Extensions

In the Supabase SQL editor run the following to enable phonetic matching used by the application:

```sql
create extension if not exists fuzzystrmatch;
```

## Step 3: Configure Environment Variables

Update your `.env` file with your Supabase credentials:

```env
DB_CONNECTION=pgsql
DB_HOST=db.your-project-ref.supabase.co
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres
DB_PASSWORD=your-supabase-password
DB_SCHEMA=public
DB_SSLMODE=require
```

Alternatively, you can use the full connection string:

```env
DATABASE_URL=postgresql://postgres:your-password@db.your-project-ref.supabase.co:5432/postgres
```

## Step 4: Import Database Schema

### Option A: Using the PostgreSQL Schema File

The application includes a PostgreSQL schema file. However, it needs to be properly formatted first.

1. Navigate to the Supabase SQL Editor in your dashboard
2. Create tables manually or use the MySQL-to-PostgreSQL converted schema

### Option B: Export from MySQL and Convert

If you have existing MySQL data:

1. **Export MySQL data**:
   ```bash
   mysqldump -u phpip -p --no-create-info --skip-triggers phpip > data.sql
   ```

2. **Export MySQL schema**:
   ```bash
   mysqldump -u phpip -p --no-data --skip-triggers phpip > schema.sql
   ```

3. **Convert schema to PostgreSQL**:
   Use a tool like `pgloader` or manually convert the schema:

   Key conversions:
   - `AUTO_INCREMENT` → `SERIAL` or `GENERATED ALWAYS AS IDENTITY`
   - `TINYINT(1)` → `BOOLEAN`
   - `DATETIME` → `TIMESTAMP`
   - `LONGTEXT` → `TEXT`
   - `` ` `` (backticks) → `"` (double quotes) for identifiers
   - `ENGINE=InnoDB` → Remove (PostgreSQL doesn't use this)
   - `utf8mb4_0900_ai_ci` → Remove (use default collation)

4. **Import schema to Supabase**:
   - Go to Supabase SQL Editor
   - Run the converted schema SQL

5. **Import data**:
   - Convert INSERT statements to PostgreSQL format
   - Run the data import in Supabase SQL Editor

## Step 5: Key Database Changes

### JSON Column Operators

The application uses JSON columns for translatable attributes. PostgreSQL syntax:

- MySQL: `JSON_UNQUOTE(JSON_EXTRACT(column, '$.key'))`
- PostgreSQL: `column->>'key'`

All such queries have been updated in the codebase.

### Aggregate Functions

- MySQL: `GROUP_CONCAT(column SEPARATOR '; ')`
- PostgreSQL: `STRING_AGG(column, '; ')`

### NULL Handling

- MySQL: `IFNULL(a, b)`
- PostgreSQL: `COALESCE(a, b)`

### Boolean Checks

- MySQL: `ISNULL(column)` or `column IS NULL`
- PostgreSQL: `column IS NULL` (standard SQL)

### Case-Insensitive Comparison

- MySQL: Uses collation (`COLLATE utf8mb4_0900_ai_ci`)
- PostgreSQL: Use `ILIKE` operator for case-insensitive LIKE queries

## Step 6: Database Views

The application uses several database views. These need to be created in PostgreSQL:

1. `event_lnk_list`
2. `matter_actors`
3. `matter_classifiers`
4. `renewal_list`
5. `task_list`
6. `users`

Refer to `database/schema/postgres-schema.sql` for view definitions (may need manual fixes).

## Step 7: Database Triggers, Functions, and Stored Procedures **CRITICAL**

**⚠️ WARNING**: The application relies HEAVILY on database triggers and stored procedures for core functionality. The application **WILL NOT WORK** without these being properly converted and tested.

### Critical Triggers

The application uses 15 triggers that handle:
- Automatic task generation when events are created
- Task recalculation when events change
- Default actor assignment
- Title case formatting
- Renewal task management

**Most Critical**:
- `event_after_insert` - Generates all tasks based on rules (very complex)
- `event_after_update` - Recalculates tasks when events change
- `task_before_insert` - Sets default assignments

### Stored Procedures (2)

1. `insert_recurring_renewals()` - Creates renewal tasks
2. `recalculate_tasks()` - Recalculates task due dates

### Stored Function (1)

1. `tcase()` - Title case conversion

### Conversion Guide

See [DATABASE_OBJECTS.md](DATABASE_OBJECTS.md) for complete conversion guide for each trigger, procedure, and function.

These need to be converted to PostgreSQL syntax:

### MySQL Trigger Example:
```sql
CREATE TRIGGER classifier_before_insert BEFORE INSERT ON classifier
FOR EACH ROW
BEGIN
  -- trigger logic
END;
```

### PostgreSQL Trigger Example:
```sql
CREATE OR REPLACE FUNCTION classifier_before_insert_fn()
RETURNS TRIGGER AS $$
BEGIN
  -- trigger logic
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER classifier_before_insert
BEFORE INSERT ON classifier
FOR EACH ROW
EXECUTE FUNCTION classifier_before_insert_fn();
```

## Step 8: Run Laravel Migrations

**Important**: The existing Laravel migrations in `database/migrations/` are MySQL-specific and should **NOT** be run on PostgreSQL.

Instead:
1. Mark all migrations as run without executing them:
   ```bash
   # This tells Laravel the migrations are already applied
   php artisan migrate:install
   # Manually insert migration records or use your imported schema
   ```

2. Or, comment out MySQL-specific code in migrations before running

## Step 9: Test the Application

1. **Clear caches**:
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

2. **Test database connection**:
   ```bash
   php artisan tinker
   >>> DB::connection()->getPdo();
   >>> \App\Models\Actor::count();
   ```

3. **Run the application**:
   ```bash
   php artisan serve
   ```

4. **Test key features**:
   - User authentication
   - Matter listing and search
   - Task management
   - Renewals
   - Actor management

## Step 10: Supabase-Specific Features (Optional)

### Row Level Security (RLS)

Supabase supports Row Level Security. You can enable this for additional security:

```sql
-- Enable RLS on a table
ALTER TABLE matter ENABLE ROW LEVEL SECURITY;

-- Create a policy (example)
CREATE POLICY "Users can view their own matters"
ON matter FOR SELECT
USING (auth.uid() = user_id);
```

### Real-time Subscriptions

Supabase supports real-time database changes. Enable for specific tables if needed.

### Supabase Auth Integration

Consider integrating Supabase Auth for authentication instead of Laravel's built-in auth.

## Troubleshooting

### Connection Issues

If you can't connect to Supabase:

1. Check your firewall settings
2. Verify SSL mode is set to `require`
3. Test connection with `psql`:
   ```bash
   psql "postgresql://postgres:password@db.project-ref.supabase.co:5432/postgres"
   ```

### JSON Column Issues

If JSON queries fail:
- Ensure JSON columns are of type `JSON` or `JSONB` (recommended)
- Use `->` for JSON object access, `->>` for text extraction
- Use `JSONB` for better performance and indexing

### Performance Issues

- Create indexes on frequently queried JSON keys:
  ```sql
  CREATE INDEX idx_country_name_en ON country ((name->>'en'));
  ```
- Use `JSONB` instead of `JSON` for better indexing
- Analyze slow queries with `EXPLAIN ANALYZE`

### Migration Error

If migrations fail:
- The old MySQL migrations are not compatible with PostgreSQL
- Use the PostgreSQL schema directly instead of running migrations
- Or create new migrations specifically for PostgreSQL

## What Was Changed - Application Code

The following files have been updated for PostgreSQL compatibility in the **application code**:

**NOTE**: Database schema objects (triggers, procedures, views) are NOT in the application code and must be created manually in Supabase.

### Configuration
- `config/database.php` - Changed default connection to `pgsql`
- `.env.example` - Updated with Supabase connection details

### Models
- `app/Models/Task.php` - Converted MySQL JSON functions to PostgreSQL
- `app/Models/Matter.php` - Converted MySQL JSON and aggregate functions

### Controllers
- `app/Http/Controllers/RuleController.php` - Updated JSON queries
- `app/Http/Controllers/CountryController.php` - Updated JSON queries
- `app/Http/Controllers/MatterController.php` - Replaced MySQL `SOUNDS LIKE` with `ILIKE` pattern matching (lines 430, 467)

### Providers
- `app/Providers/AppServiceProvider.php` - Updated `whereJsonLike` macro

### Commands
- `app/Console/Commands/RenewrSync.php` - Updated JSON queries

## Additional Resources

- [Supabase Documentation](https://supabase.com/docs)
- [PostgreSQL JSON Functions](https://www.postgresql.org/docs/current/functions-json.html)
- [Laravel PostgreSQL](https://laravel.com/docs/11.x/database#postgresql)
- [MySQL to PostgreSQL Migration Guide](https://wiki.postgresql.org/wiki/Converting_from_other_Databases_to_PostgreSQL#MySQL)

## Support

If you encounter issues during migration:
1. Check the application logs: `storage/logs/laravel.log`
2. Enable query logging in `.env`: `DB_LOG_QUERIES=true`
3. Review PostgreSQL logs in Supabase dashboard
4. Consult the PostgreSQL documentation for syntax differences

---

**Note**: This migration changes the underlying database from MySQL to PostgreSQL. While the application logic remains the same, SQL syntax differences have been addressed throughout the codebase.
