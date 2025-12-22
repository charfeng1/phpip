<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

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
    }
}
