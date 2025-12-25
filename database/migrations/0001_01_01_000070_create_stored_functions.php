<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Create PostgreSQL stored functions.
 *
 * Functions created:
 * - tcase(TEXT): Convert text to title case
 * - actor_list(INTEGER, TEXT): Get aggregated actor names for a matter/role
 * - matter_status(INTEGER): Get current status of a matter
 * - compute_matter_uid(...): Generate unique identifier for a matter
 * - insert_recurring_renewals(...): Create recurring renewal tasks
 * - update_expired(): Mark expired matters with EXP event
 *
 * Note: Trigger functions are created in the triggers migration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createTcaseFunction();
        $this->createActorListFunction();
        $this->createMatterStatusFunction();
        $this->createComputeMatterUidFunction();
        $this->createInsertRecurringRenewalsFunction();
        $this->createUpdateExpiredFunction();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP FUNCTION IF EXISTS update_expired() CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS insert_recurring_renewals(INTEGER, INTEGER, DATE, VARCHAR, VARCHAR) CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS compute_matter_uid(VARCHAR, VARCHAR, VARCHAR, VARCHAR, SMALLINT) CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS matter_status(INTEGER) CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS actor_list(INTEGER, TEXT) CASCADE');
        DB::statement('DROP FUNCTION IF EXISTS tcase(TEXT) CASCADE');
    }

    /**
     * Create the tcase function for title case conversion.
     */
    private function createTcaseFunction(): void
    {
        // Check if function exists
        $exists = DB::select("SELECT 1 FROM pg_proc WHERE proname = 'tcase'");
        if (! empty($exists)) {
            return;
        }

        DB::statement("
            CREATE OR REPLACE FUNCTION tcase(str TEXT) RETURNS TEXT AS \$\$
            DECLARE
                result TEXT := '';
                word TEXT;
                words TEXT[];
                i INTEGER;
            BEGIN
                IF str IS NULL THEN
                    RETURN NULL;
                END IF;

                words := string_to_array(str, ' ');
                FOR i IN 1..array_length(words, 1) LOOP
                    word := words[i];
                    IF length(word) > 0 THEN
                        result := result || upper(left(word, 1)) || lower(substring(word from 2));
                        IF i < array_length(words, 1) THEN
                            result := result || ' ';
                        END IF;
                    END IF;
                END LOOP;

                RETURN result;
            END;
            \$\$ LANGUAGE plpgsql IMMUTABLE
        ");
    }

    /**
     * Create the actor_list function for aggregating actors.
     */
    private function createActorListFunction(): void
    {
        $exists = DB::select("SELECT 1 FROM pg_proc WHERE proname = 'actor_list'");
        if (! empty($exists)) {
            return;
        }

        DB::statement("
            CREATE OR REPLACE FUNCTION actor_list(mid INTEGER, arole TEXT) RETURNS TEXT AS \$\$
            DECLARE
                result TEXT;
            BEGIN
                SELECT string_agg(COALESCE(actor.display_name, actor.name), ', ' ORDER BY matter_actor_lnk.display_order)
                INTO result
                FROM matter_actor_lnk
                JOIN actor ON actor.id = matter_actor_lnk.actor_id
                WHERE matter_actor_lnk.matter_id = mid
                AND matter_actor_lnk.role = arole;

                RETURN result;
            END;
            \$\$ LANGUAGE plpgsql STABLE
        ");
    }

    /**
     * Create the matter_status function.
     */
    private function createMatterStatusFunction(): void
    {
        $exists = DB::select("SELECT 1 FROM pg_proc WHERE proname = 'matter_status'");
        if (! empty($exists)) {
            return;
        }

        DB::statement("
            CREATE OR REPLACE FUNCTION matter_status(mid INTEGER) RETURNS TEXT AS \$\$
            DECLARE
                result TEXT;
            BEGIN
                SELECT event_name.name ->> 'en'
                INTO result
                FROM event
                JOIN event_name ON event.code = event_name.code
                WHERE event.matter_id = mid
                AND event_name.status_event = TRUE
                ORDER BY event.event_date DESC
                LIMIT 1;

                RETURN result;
            END;
            \$\$ LANGUAGE plpgsql STABLE
        ");
    }

    /**
     * Create the compute_matter_uid function.
     */
    private function createComputeMatterUidFunction(): void
    {
        $exists = DB::select("SELECT 1 FROM pg_proc WHERE proname = 'compute_matter_uid'");
        if (! empty($exists)) {
            return;
        }

        DB::statement("
            CREATE OR REPLACE FUNCTION compute_matter_uid(
                p_caseref VARCHAR,
                p_country VARCHAR,
                p_origin VARCHAR,
                p_type_code VARCHAR,
                p_idx SMALLINT
            ) RETURNS VARCHAR AS \$\$
            BEGIN
                RETURN p_caseref || p_country ||
                    CASE WHEN p_origin IS NOT NULL THEN '-' || p_origin ELSE '' END ||
                    CASE WHEN p_type_code IS NOT NULL THEN '-' || p_type_code ELSE '' END ||
                    CASE WHEN p_idx IS NOT NULL THEN '.' || p_idx::TEXT ELSE '' END;
            END;
            \$\$ LANGUAGE plpgsql IMMUTABLE
        ");
    }

    /**
     * Create the insert_recurring_renewals procedure.
     */
    private function createInsertRecurringRenewalsFunction(): void
    {
        $exists = DB::select("SELECT 1 FROM pg_proc WHERE proname = 'insert_recurring_renewals'");
        if (! empty($exists)) {
            return;
        }

        DB::statement("
            CREATE OR REPLACE FUNCTION insert_recurring_renewals(
                p_trigger_id INTEGER,
                p_rule_id INTEGER,
                p_base_date DATE,
                p_responsible VARCHAR(16),
                p_user VARCHAR(16)
            ) RETURNS VOID AS \$\$
            DECLARE
                first_renewal INTEGER;
                r_year INTEGER;
                base_date DATE;
                start_date DATE;
                due_date DATE;
                expiry_date DATE;
                origin CHAR(2);
            BEGIN
                SELECT ebase.event_date, estart.event_date, country.renewal_first, matter.expire_date, matter.origin
                INTO base_date, start_date, first_renewal, expiry_date, origin
                FROM country
                JOIN matter ON country.iso = matter.country
                JOIN event estart ON estart.matter_id = matter.id AND estart.id = p_trigger_id
                JOIN event ebase ON ebase.matter_id = matter.id
                WHERE country.renewal_start = estart.code
                AND country.renewal_base = ebase.code;

                -- Leave if the country has no parameters
                IF start_date IS NULL THEN
                    RETURN;
                END IF;

                base_date := LEAST(base_date, p_base_date);
                r_year := ABS(first_renewal);

                WHILE r_year <= 20 LOOP
                    IF first_renewal > 0 THEN
                        due_date := base_date + ((r_year - 1) * INTERVAL '1 year');
                    ELSE
                        due_date := start_date + ((r_year - 1) * INTERVAL '1 year');
                    END IF;

                    IF due_date > expiry_date THEN
                        RETURN;
                    END IF;

                    IF due_date < start_date THEN
                        due_date := start_date;
                    END IF;

                    -- Ignore renewals in the past beyond grace period
                    IF (due_date < CURRENT_DATE - INTERVAL '6 months' AND origin != 'WO')
                       OR (due_date < CURRENT_DATE - INTERVAL '19 months' AND origin = 'WO') THEN
                        r_year := r_year + 1;
                        CONTINUE;
                    END IF;

                    INSERT INTO task (trigger_id, code, due_date, detail, rule_used, assigned_to, creator, created_at, updated_at)
                    VALUES (p_trigger_id, 'REN', due_date, jsonb_build_object('en', r_year::TEXT), p_rule_id, p_responsible, p_user, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP);

                    r_year := r_year + 1;
                END LOOP;
            END;
            \$\$ LANGUAGE plpgsql
        ");
    }

    /**
     * Create the update_expired procedure.
     */
    private function createUpdateExpiredFunction(): void
    {
        $exists = DB::select("SELECT 1 FROM pg_proc WHERE proname = 'update_expired'");
        if (! empty($exists)) {
            return;
        }

        DB::statement("
            CREATE OR REPLACE FUNCTION update_expired() RETURNS VOID AS \$\$
            DECLARE
                rec RECORD;
            BEGIN
                FOR rec IN
                    SELECT matter.id, matter.expire_date
                    FROM matter
                    WHERE expire_date < CURRENT_DATE AND dead = FALSE
                LOOP
                    INSERT INTO event (code, matter_id, event_date, created_at, creator, updated_at)
                    VALUES ('EXP', rec.id, rec.expire_date, CURRENT_TIMESTAMP, 'system', CURRENT_TIMESTAMP)
                    ON CONFLICT DO NOTHING;
                END LOOP;
            END;
            \$\$ LANGUAGE plpgsql
        ");
    }
};
