<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $database = DB::getDatabaseName();

        // Step 0: Check MySQL version - utf8mb4_0900_ai_ci requires MySQL 8.0+
        $version = DB::selectOne('SELECT VERSION() as version')->version;
        if (str_contains($version, 'MariaDB')) {
            throw new \RuntimeException(
                'This migration requires MySQL 8.0+. MariaDB does not support utf8mb4_0900_ai_ci collation. '.
                'Consider using utf8mb4_unicode_520_ci for MariaDB instead.'
            );
        }

        // Extract major version number
        preg_match('/^(\d+)\./', $version, $matches);
        $majorVersion = (int) ($matches[1] ?? 0);
        if ($majorVersion < 8) {
            throw new \RuntimeException(
                "This migration requires MySQL 8.0+. Current version: {$version}. ".
                'The utf8mb4_0900_ai_ci collation is not available in MySQL 5.7 or earlier.'
            );
        }

        echo "MySQL version check passed: {$version}\n";

        // Step 1: Change database default collation
        DB::statement("ALTER DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");

        // Step 2: Save all foreign key constraints
        $foreignKeys = DB::select("
            SELECT
                kcu.TABLE_NAME,
                kcu.CONSTRAINT_NAME,
                kcu.COLUMN_NAME,
                kcu.REFERENCED_TABLE_NAME,
                kcu.REFERENCED_COLUMN_NAME,
                rc.UPDATE_RULE,
                rc.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                AND kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
            WHERE kcu.TABLE_SCHEMA = '{$database}'
            AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
        ");

        // Step 3: Disable foreign key checks for safer bulk operations
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            // Step 4: Drop all foreign keys
            foreach ($foreignKeys as $fk) {
                echo "Dropping FK: {$fk->CONSTRAINT_NAME} on {$fk->TABLE_NAME}\n";
                DB::statement("ALTER TABLE `{$fk->TABLE_NAME}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }

            // Step 5: Get all tables and convert them
            $tables = DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$database}' AND TABLE_TYPE = 'BASE TABLE'");

            foreach ($tables as $table) {
                $tableName = $table->TABLE_NAME;
                echo "Converting table: {$tableName}\n";
                DB::statement("ALTER TABLE `{$tableName}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
            }

            // Step 6: Recreate all foreign keys
            foreach ($foreignKeys as $fk) {
                echo "Recreating FK: {$fk->CONSTRAINT_NAME} on {$fk->TABLE_NAME}\n";
                DB::statement("
                    ALTER TABLE `{$fk->TABLE_NAME}`
                    ADD CONSTRAINT `{$fk->CONSTRAINT_NAME}`
                    FOREIGN KEY (`{$fk->COLUMN_NAME}`)
                    REFERENCES `{$fk->REFERENCED_TABLE_NAME}` (`{$fk->REFERENCED_COLUMN_NAME}`)
                    ON UPDATE {$fk->UPDATE_RULE}
                    ON DELETE {$fk->DELETE_RULE}
                ");
            }
        } finally {
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        // Step 7: Recreate all triggers with new collation
        // Get all triggers
        $triggers = DB::select("SELECT TRIGGER_NAME, EVENT_MANIPULATION, EVENT_OBJECT_TABLE FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = '{$database}'");

        // Store trigger definitions before dropping
        // Handle both 'SQL Original Statement' (MySQL 8.0) and 'Create Trigger' column names
        $triggerDefinitions = [];
        foreach ($triggers as $trigger) {
            $result = DB::select("SHOW CREATE TRIGGER `{$trigger->TRIGGER_NAME}`");
            $row = $result[0];

            // Try different column names used by various MySQL versions
            $definition = $row->{'SQL Original Statement'}
                ?? $row->{'Create Trigger'}
                ?? (property_exists($row, 'sql_original_statement') ? $row->sql_original_statement : null);

            if ($definition) {
                $triggerDefinitions[$trigger->TRIGGER_NAME] = $definition;
            } else {
                echo "WARNING: Could not extract definition for trigger: {$trigger->TRIGGER_NAME}\n";
            }
        }

        // Drop all triggers
        foreach ($triggers as $trigger) {
            DB::statement("DROP TRIGGER IF EXISTS `{$trigger->TRIGGER_NAME}`");
        }

        // Recreate triggers with new collation
        DB::statement('SET character_set_client = utf8mb4');
        DB::statement('SET collation_connection = utf8mb4_0900_ai_ci');

        foreach ($triggerDefinitions as $name => $definition) {
            // Remove DEFINER clause if it causes issues
            $definition = preg_replace('/DEFINER\s*=\s*`[^`]+`@`[^`]+`/i', '', $definition);
            $definition = trim($definition);
            if (! empty($definition)) {
                DB::unprepared($definition);
                echo "Recreated trigger: {$name}\n";
            } else {
                echo "WARNING: Empty definition for trigger: {$name}\n";
            }
        }

        // Step 7: Recreate stored procedures and functions
        $procedures = DB::select("SELECT ROUTINE_NAME, ROUTINE_TYPE FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = '{$database}'");

        $routineDefinitions = [];
        foreach ($procedures as $procedure) {
            $type = $procedure->ROUTINE_TYPE;
            $name = $procedure->ROUTINE_NAME;

            if ($type === 'PROCEDURE') {
                $result = DB::select("SHOW CREATE PROCEDURE `{$name}`");
                $routineDefinitions[$name] = ['type' => 'PROCEDURE', 'sql' => $result[0]->{'Create Procedure'}];
            } else {
                $result = DB::select("SHOW CREATE FUNCTION `{$name}`");
                $routineDefinitions[$name] = ['type' => 'FUNCTION', 'sql' => $result[0]->{'Create Function'}];
            }
        }

        // Drop and recreate routines
        foreach ($routineDefinitions as $name => $routine) {
            DB::statement("DROP {$routine['type']} IF EXISTS `{$name}`");

            // Remove DEFINER clause
            $sql = preg_replace('/DEFINER\s*=\s*`[^`]+`@`[^`]+`/i', '', $routine['sql']);
            $sql = trim($sql);
            if (! empty($sql)) {
                DB::unprepared($sql);
                echo "Recreated {$routine['type']}: {$name}\n";
            } else {
                echo "WARNING: Empty definition for {$routine['type']}: {$name}\n";
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * WARNING: This rollback will revert table and database collations but does NOT
     * restore triggers and stored procedures to their original definitions/collations.
     * For a complete rollback, you should restore from a database backup taken before
     * running this migration.
     */
    public function down(): void
    {
        $database = DB::getDatabaseName();

        echo "WARNING: Rolling back collation changes. Triggers and stored procedures will NOT be restored to original collations.\n";
        echo "For complete rollback, restore from a backup taken before this migration.\n";

        // Save foreign keys first
        $foreignKeys = DB::select("
            SELECT
                kcu.TABLE_NAME,
                kcu.CONSTRAINT_NAME,
                kcu.COLUMN_NAME,
                kcu.REFERENCED_TABLE_NAME,
                kcu.REFERENCED_COLUMN_NAME,
                rc.UPDATE_RULE,
                rc.DELETE_RULE
            FROM information_schema.KEY_COLUMN_USAGE kcu
            JOIN information_schema.REFERENTIAL_CONSTRAINTS rc
                ON kcu.CONSTRAINT_NAME = rc.CONSTRAINT_NAME
                AND kcu.CONSTRAINT_SCHEMA = rc.CONSTRAINT_SCHEMA
            WHERE kcu.TABLE_SCHEMA = '{$database}'
            AND kcu.REFERENCED_TABLE_NAME IS NOT NULL
        ");

        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        try {
            // Drop foreign keys
            foreach ($foreignKeys as $fk) {
                DB::statement("ALTER TABLE `{$fk->TABLE_NAME}` DROP FOREIGN KEY `{$fk->CONSTRAINT_NAME}`");
            }

            // Revert database collation
            DB::statement("ALTER DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Revert all tables
            $tables = DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = '{$database}' AND TABLE_TYPE = 'BASE TABLE'");

            foreach ($tables as $table) {
                $tableName = $table->TABLE_NAME;
                echo "Reverting table: {$tableName}\n";
                DB::statement("ALTER TABLE `{$tableName}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            }

            // Recreate foreign keys
            foreach ($foreignKeys as $fk) {
                DB::statement("
                    ALTER TABLE `{$fk->TABLE_NAME}`
                    ADD CONSTRAINT `{$fk->CONSTRAINT_NAME}`
                    FOREIGN KEY (`{$fk->COLUMN_NAME}`)
                    REFERENCES `{$fk->REFERENCED_TABLE_NAME}` (`{$fk->REFERENCED_COLUMN_NAME}`)
                    ON UPDATE {$fk->UPDATE_RULE}
                    ON DELETE {$fk->DELETE_RULE}
                ");
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS = 1');
        }

        echo "Table collations reverted. Note: Triggers and stored procedures retain utf8mb4_0900_ai_ci collation.\n";
    }
};
