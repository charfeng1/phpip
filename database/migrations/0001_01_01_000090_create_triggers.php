<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Create database triggers and their functions.
 *
 * Trigger functions created:
 * - classifier_before_insert_func: Auto-capitalize titles
 * - event_before_insert_func: Set event_date from linked matter
 * - event_before_update_func: Update event_date from linked matter
 * - matter_before_insert_func: Compute UID and set timestamps
 * - matter_before_update_func: Recompute UID and update timestamp
 * - task_before_insert_func: Auto-complete past tasks, set timestamps
 * - task_before_update_func: Manage done_date on completion
 *
 * Triggers created:
 * - classifier_before_insert
 * - event_before_insert
 * - event_before_update
 * - matter_before_insert
 * - matter_before_update
 * - task_before_insert
 * - task_before_update
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createClassifierTrigger();
        $this->createEventTriggers();
        $this->createMatterTriggers();
        $this->createTaskTriggers();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop triggers
        DB::statement('DROP TRIGGER IF EXISTS task_before_update ON task');
        DB::statement('DROP TRIGGER IF EXISTS task_before_insert ON task');
        DB::statement('DROP TRIGGER IF EXISTS matter_before_update ON matter');
        DB::statement('DROP TRIGGER IF EXISTS matter_before_insert ON matter');
        DB::statement('DROP TRIGGER IF EXISTS event_before_update ON event');
        DB::statement('DROP TRIGGER IF EXISTS event_before_insert ON event');
        DB::statement('DROP TRIGGER IF EXISTS classifier_before_insert ON classifier');

        // Drop trigger functions
        DB::statement('DROP FUNCTION IF EXISTS task_before_update_func() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS task_before_insert_func() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS matter_before_update_func() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS matter_before_insert_func() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS event_before_update_func() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS event_before_insert_func() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS classifier_before_insert_func() CASCADE');
    }

    /**
     * Create classifier before insert trigger.
     */
    private function createClassifierTrigger(): void
    {
        // Check if trigger already exists
        $exists = DB::select("
            SELECT 1 FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = 'classifier_before_insert'
        ");
        if (! empty($exists)) {
            return;
        }

        // Create trigger function
        DB::statement("
            CREATE OR REPLACE FUNCTION classifier_before_insert_func() RETURNS TRIGGER AS \$\$
            BEGIN
                IF NEW.type_code = 'TITEN' THEN
                    NEW.value := tcase(NEW.value);
                ELSIF NEW.type_code IN ('TIT', 'TITOF', 'TITAL') THEN
                    NEW.value := upper(left(NEW.value, 1)) || lower(substring(NEW.value from 2));
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql
        ");

        // Create trigger
        DB::statement("
            CREATE TRIGGER classifier_before_insert
                BEFORE INSERT ON classifier
                FOR EACH ROW EXECUTE FUNCTION classifier_before_insert_func()
        ");
    }

    /**
     * Create event before insert and update triggers.
     */
    private function createEventTriggers(): void
    {
        // Event before insert
        $exists = DB::select("
            SELECT 1 FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = 'event_before_insert'
        ");
        if (empty($exists)) {
            DB::statement("
                CREATE OR REPLACE FUNCTION event_before_insert_func() RETURNS TRIGGER AS \$\$
                DECLARE
                    vdate DATE;
                BEGIN
                    IF NEW.alt_matter_id IS NOT NULL THEN
                        SELECT event_date INTO vdate
                        FROM event
                        WHERE code = 'FIL' AND matter_id = NEW.alt_matter_id;

                        IF vdate IS NOT NULL THEN
                            NEW.event_date := vdate;
                        ELSE
                            NEW.event_date := CURRENT_DATE;
                        END IF;
                    END IF;
                    RETURN NEW;
                END;
                \$\$ LANGUAGE plpgsql
            ");

            DB::statement("
                CREATE TRIGGER event_before_insert
                    BEFORE INSERT ON event
                    FOR EACH ROW EXECUTE FUNCTION event_before_insert_func()
            ");
        }

        // Event before update
        $exists = DB::select("
            SELECT 1 FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = 'event_before_update'
        ");
        if (empty($exists)) {
            DB::statement("
                CREATE OR REPLACE FUNCTION event_before_update_func() RETURNS TRIGGER AS \$\$
                DECLARE
                    vdate DATE;
                BEGIN
                    IF NEW.alt_matter_id IS NOT NULL THEN
                        SELECT event_date INTO vdate
                        FROM event
                        WHERE code = 'FIL' AND matter_id = NEW.alt_matter_id;

                        IF vdate IS NOT NULL THEN
                            NEW.event_date := vdate;
                        END IF;
                    END IF;
                    RETURN NEW;
                END;
                \$\$ LANGUAGE plpgsql
            ");

            DB::statement("
                CREATE TRIGGER event_before_update
                    BEFORE UPDATE ON event
                    FOR EACH ROW EXECUTE FUNCTION event_before_update_func()
            ");
        }
    }

    /**
     * Create matter before insert and update triggers.
     */
    private function createMatterTriggers(): void
    {
        // Matter before insert
        $exists = DB::select("
            SELECT 1 FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = 'matter_before_insert'
        ");
        if (empty($exists)) {
            DB::statement("
                CREATE OR REPLACE FUNCTION matter_before_insert_func() RETURNS TRIGGER AS \$\$
                BEGIN
                    NEW.uid := compute_matter_uid(NEW.caseref, NEW.country, NEW.origin, NEW.type_code, NEW.idx);
                    NEW.created_at := CURRENT_TIMESTAMP;
                    NEW.updated_at := CURRENT_TIMESTAMP;
                    RETURN NEW;
                END;
                \$\$ LANGUAGE plpgsql
            ");

            DB::statement("
                CREATE TRIGGER matter_before_insert
                    BEFORE INSERT ON matter
                    FOR EACH ROW EXECUTE FUNCTION matter_before_insert_func()
            ");
        }

        // Matter before update
        $exists = DB::select("
            SELECT 1 FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = 'matter_before_update'
        ");
        if (empty($exists)) {
            DB::statement("
                CREATE OR REPLACE FUNCTION matter_before_update_func() RETURNS TRIGGER AS \$\$
                BEGIN
                    NEW.uid := compute_matter_uid(NEW.caseref, NEW.country, NEW.origin, NEW.type_code, NEW.idx);
                    NEW.updated_at := CURRENT_TIMESTAMP;
                    RETURN NEW;
                END;
                \$\$ LANGUAGE plpgsql
            ");

            DB::statement("
                CREATE TRIGGER matter_before_update
                    BEFORE UPDATE ON matter
                    FOR EACH ROW EXECUTE FUNCTION matter_before_update_func()
            ");
        }
    }

    /**
     * Create task before insert and update triggers.
     */
    private function createTaskTriggers(): void
    {
        // Task before insert
        $exists = DB::select("
            SELECT 1 FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = 'task_before_insert'
        ");
        if (empty($exists)) {
            DB::statement("
                CREATE OR REPLACE FUNCTION task_before_insert_func() RETURNS TRIGGER AS \$\$
                BEGIN
                    IF NEW.due_date IS NOT NULL AND NEW.done IS NULL THEN
                        IF NEW.due_date <= CURRENT_DATE THEN
                            NEW.done := TRUE;
                            NEW.done_date := NEW.due_date;
                        ELSE
                            NEW.done := FALSE;
                        END IF;
                    END IF;
                    NEW.created_at := CURRENT_TIMESTAMP;
                    NEW.updated_at := CURRENT_TIMESTAMP;
                    RETURN NEW;
                END;
                \$\$ LANGUAGE plpgsql
            ");

            DB::statement("
                CREATE TRIGGER task_before_insert
                    BEFORE INSERT ON task
                    FOR EACH ROW EXECUTE FUNCTION task_before_insert_func()
            ");
        }

        // Task before update
        $exists = DB::select("
            SELECT 1 FROM information_schema.triggers
            WHERE trigger_schema = 'public' AND trigger_name = 'task_before_update'
        ");
        if (empty($exists)) {
            DB::statement("
                CREATE OR REPLACE FUNCTION task_before_update_func() RETURNS TRIGGER AS \$\$
                BEGIN
                    IF NEW.done = TRUE AND OLD.done = FALSE THEN
                        IF NEW.done_date IS NULL THEN
                            NEW.done_date := CURRENT_DATE;
                        END IF;
                    END IF;
                    IF NEW.done = FALSE AND OLD.done = TRUE THEN
                        NEW.done_date := NULL;
                    END IF;
                    NEW.updated_at := CURRENT_TIMESTAMP;
                    RETURN NEW;
                END;
                \$\$ LANGUAGE plpgsql
            ");

            DB::statement("
                CREATE TRIGGER task_before_update
                    BEFORE UPDATE ON task
                    FOR EACH ROW EXECUTE FUNCTION task_before_update_func()
            ");
        }
    }
};
