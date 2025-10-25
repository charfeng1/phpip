# Database Objects Reference

This document lists all database objects (tables, views, triggers, stored procedures, and functions) in the phpIP MySQL schema that need to be created/converted for Supabase (PostgreSQL).

## Tables (21 total)

All tables need to be created in PostgreSQL. Key differences to address:

### Table List

1. `actor` - User and contact information
2. `actor_role` - Role definitions
3. `classifier` - Matter classifications
4. `classifier_type` - Classification types
5. `classifier_value` - Classification values
6. `country` - Country data with renewal information
7. `default_actor` - Default actor assignments
8. `event` - Matter events
9. `event_class_lnk` - Event-to-template class links
10. `event_name` - Event name definitions
11. `failed_jobs` - Laravel queue failed jobs
12. `fees` - Fee structures
13. `matter` - IP matters/cases
14. `matter_actor_lnk` - Matter-to-actor relationships
15. `matter_category` - Matter categories
16. `matter_type` - Matter type definitions
17. `renewals_log` - Renewal logging
18. `task` - Tasks
19. `task_rules` - Task generation rules
20. `template_classes` - Document template classes
21. `template_members` - Template members

### Key Table Conversions

#### Data Type Conversions

- `AUTO_INCREMENT` → `GENERATED ALWAYS AS IDENTITY` or `SERIAL`
- `TINYINT(1)` → `BOOLEAN`
- `DATETIME` → `TIMESTAMP`
- `LONGTEXT` → `TEXT`
- `MEDIUMBLOB` → `BYTEA`
- `DOUBLE(8,2)` → `NUMERIC(8,2)` or `DECIMAL(8,2)`
- `CHAR(n)` → `CHAR(n)` (same)
- `VARCHAR(n)` → `VARCHAR(n)` (same)

#### Collation

- Remove all `COLLATE utf8mb4_0900_ai_ci` and `COLLATE utf8mb4_unicode_ci`
- PostgreSQL uses different collation system
- Case-insensitive comparisons use `ILIKE` instead

#### JSON Columns

The following tables have JSON columns for translatable attributes:

- `actor_role.name` - JSON
- `classifier_type.type` - JSON
- `event_name.name` - JSON
- `matter_category.category` - JSON
- `matter_type.type` - JSON
- `task.detail` - JSON (translatable task descriptions)
- `task_rules.detail` - JSON
- `country.name` - JSON (multi-language country names)

**PostgreSQL Note**: Use `JSONB` instead of `JSON` for better performance and indexing.

## Database Views (6 total)

All views need to be recreated in PostgreSQL with proper syntax conversion.

### 1. `event_lnk_list`

Combines events with linked matter information.

**Contains**: Event data with matter references and alternative matter links

**Conversion needed**:
- `IFNULL()` → `COALESCE()`
- JSON extraction functions

### 2. `matter_actors`

Shows actors with inherited relationships from containers.

**Contains**: Matter-actor relationships including inherited actors from container matters

**Conversion needed**:
- `IFNULL()` → `COALESCE()`
- Join syntax verification

### 3. `matter_classifiers`

Displays classifiers with type information.

**Contains**: Classifier data including type information and main display flags

**Conversion needed**:
- `IFNULL()` → `COALESCE()`
- JSON column handling

### 4. `renewal_list`

Renewal tasks with full matter/actor details - very complex view.

**Contains**: Complete renewal information with fees, actors, titles, and matter data

**Conversion needed**:
- `IFNULL()` → `COALESCE()`
- `GROUP_CONCAT()` → `STRING_AGG()`
- JSON extraction: `JSON_UNQUOTE(JSON_EXTRACT())` → `->>` or `->>`
- Complex join conditions

### 5. `task_list`

Tasks with related matter and event information.

**Contains**: Task data with event, matter, and actor relationships

**Conversion needed**:
- `IFNULL()` → `COALESCE()`
- `GROUP_CONCAT()` → `STRING_AGG()`
- JSON extraction functions

### 6. `users`

View of actors with login credentials.

**Contains**: Actor data filtered for users (with login)

**Conversion needed**:
- Basic SELECT, should work as-is

## Stored Functions (1 total)

### 1. `tcase(str TEXT)`

**Purpose**: Title case conversion - capitalizes first letter after punctuation

**Usage**: Called by `classifier_before_insert` trigger

**MySQL Code Summary**:
```sql
-- Loops through string, capitalizes letters after punctuation
-- Punctuation: ' ()[]{},.-_!@;:?/'
```

**PostgreSQL Conversion**: Need to rewrite using PL/pgSQL:

```sql
CREATE OR REPLACE FUNCTION tcase(str TEXT)
RETURNS TEXT AS $$
DECLARE
    c CHAR(1);
    s TEXT;
    i INT := 1;
    bool BOOLEAN := TRUE;
    punct TEXT := ' ()[]{},.-_!@;:?/';
BEGIN
    s := LOWER(str);
    WHILE i <= LENGTH(str) LOOP
        c := SUBSTRING(s, i, 1);
        IF POSITION(c IN punct) > 0 THEN
            bool := TRUE;
        ELSIF bool THEN
            IF c >= 'a' AND c <= 'z' THEN
                s := CONCAT(LEFT(s, i-1), UPPER(c), SUBSTRING(s, i+1));
                bool := FALSE;
            ELSIF c >= '0' AND c <= '9' THEN
                bool := FALSE;
            END IF;
        END IF;
        i := i + 1;
    END LOOP;
    RETURN s;
END;
$$ LANGUAGE plpgsql IMMUTABLE;
```

## Stored Procedures (2 total)

### 1. `insert_recurring_renewals()`

**Parameters**:
- `P_trigger_id INT`
- `P_rule_id INT`
- `P_base_date DATE`
- `P_responsible CHAR(16)`
- `P_user CHAR(16)`

**Purpose**: Inserts recurring renewal tasks based on country renewal parameters

**Called by**: `event_after_insert` trigger

**Conversion needed**:
- `DECLARE` syntax (PostgreSQL uses different format)
- `LEAVE` → `RETURN`
- `ITERATE` → `CONTINUE`
- Loop syntax
- Date arithmetic: `+ INTERVAL` syntax is similar but verify
- `ABS()`, `LEAST()` - should work as-is
- `Now()` → `CURRENT_TIMESTAMP` or keep `NOW()`

### 2. `recalculate_tasks()`

**Parameters**:
- `P_matter_id INT`
- `P_event_code CHAR(5)`
- `P_user CHAR(16)`

**Purpose**: Recalculates task due dates when events change

**Called by**: `event_after_update` and `event_after_delete` triggers

**Conversion needed**:
- Cursor syntax differences
- `DECLARE CONTINUE HANDLER` → exception handling
- `CAST(...AS DATE)` syntax verification
- `INTERVAL` arithmetic
- `LAST_DAY()` function → PostgreSQL equivalent

## Database Triggers (12 total)

All triggers need to be converted to PostgreSQL trigger functions.

### Trigger Conversion Pattern

**MySQL**:
```sql
CREATE TRIGGER trigger_name BEFORE INSERT ON table_name
FOR EACH ROW
BEGIN
  -- logic
END;
```

**PostgreSQL**:
```sql
CREATE OR REPLACE FUNCTION trigger_name_fn()
RETURNS TRIGGER AS $$
BEGIN
  -- logic
  RETURN NEW; -- or OLD for DELETE
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trigger_name
BEFORE INSERT ON table_name
FOR EACH ROW
EXECUTE FUNCTION trigger_name_fn();
```

### Trigger List

#### 1. `classifier_before_insert`

**Table**: `classifier`
**Timing**: BEFORE INSERT
**Purpose**: Formats classifier values based on type (title case for titles)

**Logic**:
- If type = 'TITEN': Apply `tcase()` function
- If type in ('TIT', 'TITOF', 'TITAL'): Capitalize first letter only

**Conversion notes**:
- Need to convert `tcase()` function first
- `CONCAT()`, `UCASE()`, `SUBSTR()` → PostgreSQL equivalents
- `LCASE()` → `LOWER()`
- `UCASE()` → `UPPER()`
- `SUBSTR()` → `SUBSTRING()`

#### 2. `event_before_insert`

**Table**: `event`
**Timing**: BEFORE INSERT
**Purpose**: Auto-fill event date from linked matter's filing date

**Logic**:
- If `alt_matter_id` is set, copy filing date from linked matter
- Otherwise set to current date

**Conversion notes**:
- `Now()` → `CURRENT_TIMESTAMP` or `NOW()`
- EXISTS subquery should work as-is

#### 3. `event_after_insert`

**Table**: `event`
**Timing**: AFTER INSERT
**Purpose**: **COMPLEX** - Generates tasks based on rules when events are inserted

**Logic**:
- Fetches applicable rules from `task_rules`
- Calculates due dates based on rule parameters
- Creates tasks
- Calls `insert_recurring_renewals()` for renewal tasks
- Handles task deletion/clearing based on rules
- Updates matter expiry dates

**Conversion notes**:
- Very complex cursor logic
- Multiple nested cursors
- Calls stored procedure
- Date calculations with `INTERVAL`
- `LAST_DAY()` → Use PostgreSQL date functions
- `LEAST()`, `GREATEST()` should work
- `IFNULL()` → `COALESCE()`

#### 4. `event_before_update`

**Table**: `event`
**Timing**: BEFORE UPDATE
**Purpose**: Updates event date from linked matter

**Conversion notes**:
- Similar to `event_before_insert`
- Simple conversion

#### 5. `event_after_update`

**Table**: `event`
**Timing**: AFTER UPDATE
**Purpose**: **COMPLEX** - Recalculates tasks when event dates change

**Logic**:
- Updates task due dates based on changed event dates
- Handles cascading updates to linked matters
- Uses cursors for batch updates

**Conversion notes**:
- Multiple cursors
- Date arithmetic
- Nested queries

#### 6. `event_after_delete`

**Table**: `event`
**Timing**: AFTER DELETE
**Purpose**: Cleans up when events are deleted

**Logic**:
- Calls `recalculate_tasks()` for PRI/PFIL events
- Clears matter expiry date for FIL events
- Resets matter.dead flag

**Conversion notes**:
- Stored procedure call
- Subquery with NOT EXISTS

#### 7. `ename_after_update`

**Table**: `event_name`
**Timing**: AFTER UPDATE
**Purpose**: Updates task assignments when event name's default responsible changes

**Conversion notes**:
- `<=>` NULL-safe comparison → `IS NOT DISTINCT FROM` in PostgreSQL

#### 8. `matter_after_insert`

**Table**: `matter`
**Timing**: AFTER INSERT
**Purpose**: Creates initial 'CRE' event and assigns default actors

**Logic**:
- Inserts creation event
- Assigns default actor based on country/category from `default_actor` table

**Conversion notes**:
- Straightforward INSERT statements
- Subquery logic should work

#### 9. `matter_before_update`

**Table**: `matter`
**Timing**: BEFORE UPDATE
**Purpose**: Adjusts expiry date when term adjustment changes

**Conversion notes**:
- Simple date arithmetic
- `INTERVAL DAY` should work

#### 10. `matter_after_update`

**Table**: `matter`
**Timing**: AFTER UPDATE
**Purpose**: Updates task assignments when matter responsible changes

**Conversion notes**:
- JOIN in UPDATE statement (PostgreSQL supports this)

#### 11. `malnk_after_insert` / `matter_actor_lnk_AFTER_INSERT`

**Table**: `matter_actor_lnk`
**Timing**: AFTER INSERT
**Purpose**: Deletes renewal tasks when CLIENT is set as annuity agent

**Conversion notes**:
- DELETE with JOIN
- Straightforward conversion

#### 12. `matter_actor_lnk_AFTER_UPDATE`

**Table**: `matter_actor_lnk`
**Timing**: AFTER UPDATE
**Purpose**: Same as insert trigger

#### 13. `task_before_insert`

**Table**: `task`
**Timing**: BEFORE INSERT
**Purpose**: Sets default assigned_to value

**Conversion notes**:
- Nested SELECT statements
- `IFNULL()` → `COALESCE()`

#### 14. `task_before_update`

**Table**: `task`
**Timing**: BEFORE UPDATE
**Purpose**: Auto-manages done/done_date relationship

**Logic**:
- Sets `done=1` when `done_date` is set
- Clears `done_date` when `done=0`
- Auto-fills `done_date` when marking task done

**Conversion notes**:
- `Least()` → `LEAST()` (same)
- `Now()` → `CURRENT_TIMESTAMP` or `NOW()`

#### 15. `trules_after_update`

**Table**: `task_rules`
**Timing**: AFTER UPDATE
**Purpose**: Updates task fees/costs when rules change

**Conversion notes**:
- Simple UPDATE with JOIN

## Foreign Key Constraints

All foreign key constraints need to be recreated. The schema uses:

- `ON DELETE CASCADE` - Automatically delete related records
- `ON DELETE SET NULL` - Set to NULL when parent deleted
- `ON DELETE RESTRICT` - Prevent deletion if children exist
- `ON UPDATE CASCADE` - Update foreign key when parent key changes

PostgreSQL supports all these constraint actions.

## Indexes

Key indexes to recreate:

- Primary keys (automatic with SERIAL/IDENTITY)
- Unique keys
- Foreign key indexes
- Search indexes on `name`, `caseref`, `uid`, etc.
- JSON path indexes for translatable columns

### JSON Indexes in PostgreSQL

For JSON columns, create GIN indexes:

```sql
CREATE INDEX idx_country_name_en ON country USING GIN ((name->'en'));
CREATE INDEX idx_event_name_name ON event_name USING GIN (name);
```

Or for specific paths:

```sql
CREATE INDEX idx_country_name_en ON country ((name->>'en'));
```

## Migration Strategy

### Phase 1: Schema

1. Convert and create all tables
2. Create all indexes
3. Create foreign key constraints

### Phase 2: Programmability

1. Convert and create functions (`tcase`)
2. Convert and create stored procedures
3. Convert and create all triggers

### Phase 3: Data

1. Export MySQL data
2. Transform data (if needed)
3. Import to PostgreSQL

### Phase 4: Views

1. Create all 6 database views
2. Test view queries

### Phase 5: Testing

1. Test all CRUD operations
2. Test trigger functionality
3. Test stored procedures
4. Verify data integrity

## Important Notes

1. **Triggers are critical**: The application relies heavily on triggers for automatic task generation and management. These MUST be properly converted for the application to function.

2. **Test thoroughly**: The `event_after_insert` trigger is very complex and is core to the application's business logic.

3. **JSON functions**: All JSON column queries in the application code have been converted to PostgreSQL syntax.

4. **Collation**: PostgreSQL doesn't have the same collation as MySQL. Use `CITEXT` type or `ILIKE` for case-insensitive comparisons.

5. **Performance**: Consider using `JSONB` instead of `JSON` for better performance and indexing capabilities.

6. **Extensions**: You may want to enable PostgreSQL extensions:
   - `pg_trgm` - For similarity searching (alternative to SOUNDS LIKE)
   - `fuzzystrmatch` - For SOUNDEX functions
   - `citext` - For case-insensitive text

## Testing Checklist

- [ ] All tables created
- [ ] All indexes created
- [ ] All foreign keys created
- [ ] All views created and queryable
- [ ] `tcase()` function works
- [ ] Both stored procedures work
- [ ] All 15 triggers work correctly
- [ ] Task generation works (critical!)
- [ ] Renewal task generation works
- [ ] Matter/event CRUD operations work
- [ ] Task CRUD operations work
- [ ] Actor assignment works
- [ ] JSON column queries work
- [ ] Multi-language support works

