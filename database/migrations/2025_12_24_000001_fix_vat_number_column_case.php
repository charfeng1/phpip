<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix VAT_number column case sensitivity in PostgreSQL.
     *
     * PostgreSQL folds unquoted identifiers to lowercase, so the column
     * is stored as 'vat_number' but Laravel/seeders use 'VAT_number'.
     * This migration renames the column to preserve the mixed case.
     */
    public function up(): void
    {
        // Skip for MySQL (case-insensitive by default)
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        // Check if the lowercase column exists and rename it
        $hasLowercase = DB::select("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'actor'
            AND column_name = 'vat_number'
        ");

        if (! empty($hasLowercase)) {
            DB::statement('ALTER TABLE actor RENAME COLUMN vat_number TO "VAT_number"');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        $hasMixedCase = DB::select("
            SELECT column_name
            FROM information_schema.columns
            WHERE table_name = 'actor'
            AND column_name = 'VAT_number'
        ");

        if (! empty($hasMixedCase)) {
            DB::statement('ALTER TABLE actor RENAME COLUMN "VAT_number" TO vat_number');
        }
    }
};
