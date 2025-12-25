<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run()
    {
        $this->call(CountryTableSeeder::class);

        // Translatable table seeders (insert multi-language JSON directly)
        // Order matters due to foreign key constraints
        $this->call(MatterCategoryTableSeeder::class);  // Must be before EventName (FK)
        $this->call(MatterTypeTableSeeder::class);
        $this->call(ActorRoleTableSeeder::class);
        $this->call(ClassifierTypeTableSeeder::class);
        $this->call(EventNameTableSeeder::class);       // Depends on MatterCategory
        $this->call(TaskRulesTableSeeder::class);       // Depends on EventName

        $this->call(ActorTableSeeder::class);
        $this->call(FeesTableSeeder::class);
        $this->call(TemplateClassesTableSeeder::class);
        $this->call(TemplateMembersTableSeeder::class);

        // Reset sequences to avoid conflicts with factory-generated records
        // Seeders use explicit IDs, so sequences need to be updated to MAX(id) + 1
        $this->resetSequences();
    }

    /**
     * Reset all PostgreSQL sequences to match the current max ID in each table.
     * This prevents unique constraint violations when factories create new records.
     */
    private function resetSequences(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("
            DO \$\$
            DECLARE
                seq_name TEXT;
                tbl_name TEXT;
            BEGIN
                FOR seq_name, tbl_name IN
                    SELECT sequence_name::text, substring(sequence_name from '(.*)_id_seq\$')::text
                    FROM information_schema.sequences
                    WHERE sequence_schema = 'public' AND sequence_name LIKE '%_id_seq'
                LOOP
                    EXECUTE format('SELECT setval(''%I'', COALESCE((SELECT MAX(id) FROM %I), 1))', seq_name, tbl_name);
                END LOOP;
            END \$\$;
        ");
    }
}
