<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Create PostgreSQL extensions required by phpIP.
 *
 * This migration creates the uuid-ossp extension for UUID generation.
 * It uses CREATE EXTENSION IF NOT EXISTS for idempotent execution.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS "uuid-ossp"');
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
