<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
            DB::statement("
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
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
            DB::statement("
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
            ");
        }
    }
};
