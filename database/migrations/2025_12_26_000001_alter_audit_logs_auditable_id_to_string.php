<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Change auditable_id from bigint to string to support models with string primary keys.
 *
 * Models like Category, ClassifierType, EventName, MatterType, and Role use
 * string 'code' as their primary key instead of an integer ID.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the existing index first
        DB::statement('DROP INDEX IF EXISTS audit_logs_auditable_index');

        // Change the column type from bigint to varchar
        DB::statement('ALTER TABLE audit_logs ALTER COLUMN auditable_id TYPE VARCHAR(255)');

        // Recreate the index
        DB::statement('CREATE INDEX audit_logs_auditable_index ON audit_logs (auditable_type, auditable_id)');

        // Update the comment
        DB::statement("COMMENT ON COLUMN audit_logs.auditable_id IS 'Primary key of the audited record (supports both integer and string PKs)'");
    }

    /**
     * Reverse the migrations.
     *
     * @throws \LogicException if non-numeric auditable_id values exist
     */
    public function down(): void
    {
        // Check for non-numeric values before attempting rollback
        // This prevents a failed migration that would leave the database in an inconsistent state
        if (DB::table('audit_logs')->whereRaw("auditable_id ~ '[^0-9]'")->exists()) {
            throw new \LogicException(
                'Cannot reverse migration: non-numeric auditable_id values exist in audit_logs table. '.
                'Delete or migrate these records before rolling back.'
            );
        }

        // Drop the index
        DB::statement('DROP INDEX IF EXISTS audit_logs_auditable_index');

        // Safe to convert since we verified all values are numeric
        DB::statement('ALTER TABLE audit_logs ALTER COLUMN auditable_id TYPE BIGINT USING auditable_id::BIGINT');

        // Recreate the index
        DB::statement('CREATE INDEX audit_logs_auditable_index ON audit_logs (auditable_type, auditable_id)');

        // Restore original comment
        DB::statement("COMMENT ON COLUMN audit_logs.auditable_id IS 'Primary key of the audited record'");
    }
};
