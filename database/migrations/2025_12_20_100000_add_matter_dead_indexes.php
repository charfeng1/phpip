<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds performance indexes for the matter table:
     * - Single index on 'dead' column for filtering active/dead matters
     * - Composite index on (caseref, dead) for family-based queries
     *
     * These indexes improve query performance for common operations:
     * - Filtering matters by dead status
     * - Querying family members (same caseref) that are active/dead
     * - Combined caseref + dead filters in matter lists
     */
    public function up(): void
    {
        Schema::table('matter', function (Blueprint $table) {
            // Index for filtering by dead status
            $table->index('dead', 'idx_matter_dead');

            // Composite index for caseref + dead queries (family filtering)
            $table->index(['caseref', 'dead'], 'idx_matter_caseref_dead');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matter', function (Blueprint $table) {
            $table->dropIndex('idx_matter_dead');
            $table->dropIndex('idx_matter_caseref_dead');
        });
    }
};
