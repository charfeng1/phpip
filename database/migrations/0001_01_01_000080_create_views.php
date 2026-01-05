<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Create database views.
 *
 * Views created:
 * - users: Laravel auth view on actor table (CRITICAL for authentication)
 * - event_lnk_list: Priority claims with linked matter data
 * - matter_actors: Aggregated actor information per matter
 * - matter_classifiers: Aggregated classifier information per matter
 * - task_list: Task display with matter and event details
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createEventLnkListView();
        $this->createMatterActorsView();
        $this->createMatterClassifiersView();
        $this->createTaskListView();
        $this->createUsersView();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS users CASCADE');
        DB::statement('DROP VIEW IF EXISTS task_list CASCADE');
        DB::statement('DROP VIEW IF EXISTS matter_classifiers CASCADE');
        DB::statement('DROP VIEW IF EXISTS matter_actors CASCADE');
        DB::statement('DROP VIEW IF EXISTS event_lnk_list CASCADE');
    }

    /**
     * Create the event_lnk_list view.
     */
    private function createEventLnkListView(): void
    {
        $exists = DB::select("SELECT 1 FROM pg_views WHERE schemaname = 'public' AND viewname = 'event_lnk_list'");
        if (! empty($exists)) {
            return;
        }

        DB::statement("
            CREATE OR REPLACE VIEW event_lnk_list AS
            SELECT
                event.id,
                event.code,
                event.matter_id,
                CASE WHEN event.alt_matter_id IS NULL THEN event.event_date ELSE lnk.event_date END AS event_date,
                CASE WHEN event.alt_matter_id IS NULL THEN event.detail ELSE lnk.detail END AS detail,
                matter.country
            FROM event
            LEFT JOIN event lnk ON event.alt_matter_id = lnk.matter_id AND lnk.code = 'FIL'
            LEFT JOIN matter ON event.alt_matter_id = matter.id
        ");
    }

    /**
     * Create the matter_actors view.
     */
    private function createMatterActorsView(): void
    {
        $exists = DB::select("SELECT 1 FROM pg_views WHERE schemaname = 'public' AND viewname = 'matter_actors'");
        if (! empty($exists)) {
            return;
        }

        DB::statement("
            CREATE OR REPLACE VIEW matter_actors AS
            -- Direct actor links (not shared)
            SELECT
                pivot.id,
                actor.id AS actor_id,
                COALESCE(actor.display_name, actor.name) AS display_name,
                actor.name,
                actor.first_name,
                actor.email,
                pivot.display_order,
                pivot.role AS role_code,
                actor_role.name ->> 'en' AS role_name,
                actor_role.shareable,
                actor_role.show_ref,
                actor_role.show_company,
                actor_role.show_rate,
                actor_role.show_date,
                matter.id AS matter_id,
                actor.warn,
                pivot.actor_ref,
                pivot.date,
                pivot.rate,
                pivot.shared,
                co.name AS company,
                0 AS inherited
            FROM matter_actor_lnk pivot
            JOIN matter ON pivot.matter_id = matter.id
            JOIN actor ON pivot.actor_id = actor.id
            LEFT JOIN actor co ON co.id = pivot.company_id
            JOIN actor_role ON pivot.role = actor_role.code
            WHERE pivot.shared = FALSE

            UNION ALL

            -- Shared actor links (inherited from container)
            SELECT
                pivot.id,
                actor.id AS actor_id,
                COALESCE(actor.display_name, actor.name) AS display_name,
                actor.name,
                actor.first_name,
                actor.email,
                pivot.display_order,
                pivot.role AS role_code,
                actor_role.name ->> 'en' AS role_name,
                actor_role.shareable,
                actor_role.show_ref,
                actor_role.show_company,
                actor_role.show_rate,
                actor_role.show_date,
                matter.id AS matter_id,
                actor.warn,
                pivot.actor_ref,
                pivot.date,
                pivot.rate,
                pivot.shared,
                co.name AS company,
                1 AS inherited
            FROM matter_actor_lnk pivot
            JOIN matter ON pivot.matter_id = matter.container_id
            JOIN actor ON pivot.actor_id = actor.id
            LEFT JOIN actor co ON co.id = pivot.company_id
            JOIN actor_role ON pivot.role = actor_role.code
            WHERE pivot.shared = TRUE

            ORDER BY role_code, display_order
        ");
    }

    /**
     * Create the matter_classifiers view.
     */
    private function createMatterClassifiersView(): void
    {
        $exists = DB::select("SELECT 1 FROM pg_views WHERE schemaname = 'public' AND viewname = 'matter_classifiers'");
        if (! empty($exists)) {
            return;
        }

        DB::statement("
            CREATE OR REPLACE VIEW matter_classifiers AS
            -- Classifiers for matters without containers
            SELECT
                classifier.id,
                matter.id AS matter_id,
                classifier.type_code,
                classifier_type.type ->> 'en' AS type_name,
                classifier_type.main_display,
                CASE WHEN classifier.value_id IS NULL THEN classifier.value ELSE classifier_value.value END AS value,
                classifier.url,
                classifier.lnk_matter_id,
                classifier.display_order
            FROM classifier
            JOIN classifier_type ON classifier.type_code = classifier_type.code
            JOIN matter ON matter.id = classifier.matter_id
            LEFT JOIN classifier_value ON classifier_value.id = classifier.value_id
            WHERE matter.container_id IS NULL

            UNION ALL

            -- Classifiers for matters with containers (use container's classifiers)
            SELECT
                classifier.id,
                matter.id AS matter_id,
                classifier.type_code,
                classifier_type.type ->> 'en' AS type_name,
                classifier_type.main_display,
                CASE WHEN classifier.value_id IS NULL THEN classifier.value ELSE classifier_value.value END AS value,
                classifier.url,
                classifier.lnk_matter_id,
                classifier.display_order
            FROM classifier
            JOIN classifier_type ON classifier.type_code = classifier_type.code
            JOIN matter ON matter.container_id = classifier.matter_id
            LEFT JOIN classifier_value ON classifier_value.id = classifier.value_id
            WHERE matter.container_id IS NOT NULL

            ORDER BY display_order
        ");
    }

    /**
     * Create the task_list view.
     */
    private function createTaskListView(): void
    {
        $exists = DB::select("SELECT 1 FROM pg_views WHERE schemaname = 'public' AND viewname = 'task_list'");
        if (! empty($exists)) {
            return;
        }

        DB::statement("
            CREATE OR REPLACE VIEW task_list AS
            -- Tasks for matters without containers
            SELECT
                task.id,
                task.code,
                event_name.name ->> 'en' AS name,
                task.detail,
                task.due_date,
                task.done,
                task.done_date,
                event.matter_id,
                task.cost,
                task.fee,
                task.trigger_id,
                matter.category_code AS category,
                matter.caseref,
                matter.country,
                matter.origin,
                matter.type_code,
                matter.idx,
                COALESCE(task.assigned_to, matter.responsible) AS responsible,
                actor.login AS delegate,
                task.rule_used,
                matter.dead
            FROM matter
            LEFT JOIN matter_actor_lnk ON matter.id = matter_actor_lnk.matter_id AND matter_actor_lnk.role = 'DEL'
            LEFT JOIN actor ON actor.id = matter_actor_lnk.actor_id
            JOIN event ON matter.id = event.matter_id
            JOIN task ON task.trigger_id = event.id
            JOIN event_name ON task.code = event_name.code
            WHERE matter.container_id IS NULL

            UNION ALL

            -- Tasks for matters with containers (use container's delegate)
            SELECT
                task.id,
                task.code,
                event_name.name ->> 'en' AS name,
                task.detail,
                task.due_date,
                task.done,
                task.done_date,
                event.matter_id,
                task.cost,
                task.fee,
                task.trigger_id,
                matter.category_code AS category,
                matter.caseref,
                matter.country,
                matter.origin,
                matter.type_code,
                matter.idx,
                COALESCE(task.assigned_to, matter.responsible) AS responsible,
                actor.login AS delegate,
                task.rule_used,
                matter.dead
            FROM matter
            LEFT JOIN matter_actor_lnk ON matter.container_id = matter_actor_lnk.matter_id AND matter_actor_lnk.role = 'DEL'
            LEFT JOIN actor ON actor.id = matter_actor_lnk.actor_id
            JOIN event ON matter.id = event.matter_id
            JOIN task ON task.trigger_id = event.id
            JOIN event_name ON task.code = event_name.code
            WHERE matter.container_id IS NOT NULL
        ");
    }

    /**
     * Create the users view for Laravel authentication.
     *
     * CRITICAL: This view is required for Laravel's authentication system.
     * It maps the actor table to the expected 'users' table structure.
     */
    private function createUsersView(): void
    {
        $exists = DB::select("SELECT 1 FROM pg_views WHERE schemaname = 'public' AND viewname = 'users'");
        if (! empty($exists)) {
            return;
        }

        DB::statement('
            CREATE OR REPLACE VIEW users AS
            SELECT
                id,
                login AS name,
                login,
                email,
                password,
                default_role,
                language,
                remember_token,
                created_at,
                updated_at
            FROM actor
            WHERE login IS NOT NULL
        ');
    }
};
