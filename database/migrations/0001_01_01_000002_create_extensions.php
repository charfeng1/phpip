<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Create PostgreSQL extensions required by phpIP.
 *
 * This migration creates the uuid-ossp extension for UUID generation.
 * It checks if the extension exists before creating to support both
 * fresh installs and upgrades from baseline migration.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if extension already exists (from baseline migration)
        $exists = DB::select("SELECT 1 FROM pg_extension WHERE extname = 'uuid-ossp'");

        if (empty($exists)) {
            DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't drop uuid-ossp as it may be used by other applications
        // DB::statement('DROP EXTENSION IF EXISTS "uuid-ossp"');
    }
};
