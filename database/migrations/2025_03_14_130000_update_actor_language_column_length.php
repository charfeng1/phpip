<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The SQL statement to create/recreate the users view for PostgreSQL.
     */
    private string $usersViewSql = "
        CREATE OR REPLACE VIEW users AS
        SELECT
            actor.id,
            actor.name,
            actor.login,
            actor.language,
            actor.password,
            actor.default_role,
            actor.company_id,
            actor.email,
            actor.phone,
            actor.notes,
            actor.creator,
            actor.created_at,
            actor.updated_at,
            actor.updater,
            actor.remember_token
        FROM actor
        WHERE actor.login IS NOT NULL
    ";

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Only run if the actor table exists
        // This allows migrations to run on fresh databases where the core schema
        // (database/schema/postgres-schema.sql) hasn't been loaded yet
        if (!Schema::hasTable('actor')) {
            return;
        }

        // PostgreSQL: Drop dependent view before altering column
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP VIEW IF EXISTS users');
        }

        Schema::table('actor', function (Blueprint $table) {
            // Update language column type from CHAR(2) to CHAR(5)
            $table->char('language', 5)->nullable()->change();
        });

        // PostgreSQL: Recreate the users view
        if (DB::getDriverName() === 'pgsql') {
            DB::statement($this->usersViewSql);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Only run if the actor table exists
        if (!Schema::hasTable('actor')) {
            return;
        }

        // PostgreSQL: Drop dependent view before altering column
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP VIEW IF EXISTS users');
        }

        Schema::table('actor', function (Blueprint $table) {
            // Revert back to CHAR(2)
            $table->char('language', 2)->nullable()->change();
        });

        // PostgreSQL: Recreate the users view
        if (DB::getDriverName() === 'pgsql') {
            DB::statement($this->usersViewSql);
        }
    }
};
