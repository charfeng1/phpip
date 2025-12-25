<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create Tier 1 foundation tables (no foreign key dependencies).
 *
 * Tables created:
 * - country: ISO country codes and renewal parameters
 * - actor_role: Role definitions for actors (Client, Agent, Inventor, etc.)
 * - matter_category: IP matter categories (Patent, Trademark, etc.)
 * - matter_type: Matter type codes (National, PCT, EP, etc.)
 * - event_name: Event type definitions
 * - classifier_type: Classifier category definitions
 * - template_classes: Document template categories
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createCountryTable();
        $this->createActorRoleTable();
        $this->createMatterCategoryTable();
        $this->createMatterTypeTable();
        $this->createEventNameTable();
        $this->createClassifierTypeTable();
        $this->createTemplateClassesTable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse order (no FK dependencies in Tier 1)
        Schema::dropIfExists('template_classes');
        Schema::dropIfExists('classifier_type');
        Schema::dropIfExists('event_name');
        Schema::dropIfExists('matter_type');
        Schema::dropIfExists('matter_category');
        Schema::dropIfExists('actor_role');
        Schema::dropIfExists('country');
    }

    /**
     * Create the country table.
     */
    private function createCountryTable(): void
    {
        if (Schema::hasTable('country')) {
            return;
        }

        Schema::create('country', function (Blueprint $table) {
            $table->smallInteger('numcode')->nullable();
            $table->char('iso', 2)->primary();
            $table->char('iso3', 3)->nullable();
            $table->string('name_DE', 80)->nullable();
            $table->jsonb('name')->default('{}');
            $table->string('name_FR', 80)->nullable();
            $table->boolean('ep')->default(false);
            $table->boolean('wo')->default(false);
            $table->boolean('em')->default(false);
            $table->boolean('oa')->default(false);
            $table->smallInteger('renewal_first')->nullable()->default(2);
            $table->string('renewal_base', 5)->nullable()->default('FIL');
            $table->string('renewal_start', 5)->nullable()->default('FIL');
            $table->date('checked_on')->nullable();
        });

        // Add comments
        DB::statement("COMMENT ON COLUMN country.ep IS 'Flag default countries for EP ratifications'");
        DB::statement("COMMENT ON COLUMN country.wo IS 'Flag default countries for PCT national phase'");
        DB::statement("COMMENT ON COLUMN country.em IS 'Flag default countries for EU trade mark'");
        DB::statement("COMMENT ON COLUMN country.oa IS 'Flag default countries for OA national phase'");
        DB::statement("COMMENT ON COLUMN country.renewal_first IS 'The first year a renewal is due in this country from renewal_base. When negative, the date is calculated from renewal_start'");
        DB::statement("COMMENT ON COLUMN country.renewal_base IS 'The base event for calculating renewal deadlines'");
        DB::statement("COMMENT ON COLUMN country.renewal_start IS 'The event from which renewals become due'");
    }

    /**
     * Create the actor_role table.
     */
    private function createActorRoleTable(): void
    {
        if (Schema::hasTable('actor_role')) {
            return;
        }

        Schema::create('actor_role', function (Blueprint $table) {
            $table->string('code', 5)->primary();
            $table->jsonb('name')->default('{}');
            $table->smallInteger('display_order')->default(127);
            $table->boolean('shareable')->default(false);
            $table->boolean('show_ref')->default(false);
            $table->boolean('show_company')->default(false);
            $table->boolean('show_rate')->default(false);
            $table->boolean('show_date')->default(false);
            $table->string('notes', 160)->nullable();
            $table->string('creator', 20)->nullable();
            $table->string('updater', 20)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // Add comments
        DB::statement("COMMENT ON COLUMN actor_role.display_order IS 'Order of display in interface'");
        DB::statement("COMMENT ON COLUMN actor_role.shareable IS 'Indicates whether actors listed with this role are shareable for all matters of the same family'");

        // Add index on English name for searching
        DB::statement("CREATE INDEX idx_actor_role_name_en ON actor_role((name->>'en'))");
    }

    /**
     * Create the matter_category table.
     */
    private function createMatterCategoryTable(): void
    {
        if (Schema::hasTable('matter_category')) {
            return;
        }

        Schema::create('matter_category', function (Blueprint $table) {
            $table->string('code', 5)->primary();
            $table->jsonb('category')->default('{}');
            $table->string('display_with', 5)->nullable();
            $table->string('ref_prefix', 5)->nullable();
            $table->string('creator', 20)->nullable();
            $table->string('updater', 20)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        DB::statement("COMMENT ON COLUMN matter_category.display_with IS 'Display with category code'");
    }

    /**
     * Create the matter_type table.
     */
    private function createMatterTypeTable(): void
    {
        if (Schema::hasTable('matter_type')) {
            return;
        }

        Schema::create('matter_type', function (Blueprint $table) {
            $table->string('code', 5)->primary();
            $table->jsonb('type')->default('{}');
            $table->string('creator', 20)->nullable();
            $table->string('updater', 20)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Create the event_name table.
     */
    private function createEventNameTable(): void
    {
        if (Schema::hasTable('event_name')) {
            return;
        }

        Schema::create('event_name', function (Blueprint $table) {
            $table->string('code', 5)->primary();
            $table->jsonb('name')->default('{}');
            $table->string('category', 5)->nullable();
            $table->char('country', 2)->nullable();
            $table->boolean('is_task')->default(false);
            $table->boolean('status_event')->default(false);
            $table->string('default_responsible', 20)->nullable();
            $table->boolean('use_matter_resp')->default(false);
            $table->boolean('killer')->default(false);
            $table->boolean('unique')->default(false);
            $table->string('notes', 160)->nullable();
            $table->string('creator', 20)->nullable();
            $table->string('updater', 20)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // Note: Foreign keys to matter_category and country will be added
        // in a later migration after those tables exist with data

        // Add comments
        DB::statement("COMMENT ON COLUMN event_name.is_task IS 'Indicates whether the event can be used as a task'");
        DB::statement("COMMENT ON COLUMN event_name.status_event IS 'Indicates whether the event defines a new status for the matter'");
        DB::statement("COMMENT ON COLUMN event_name.use_matter_resp IS 'Use the matter responsible as default responsible'");
        DB::statement("COMMENT ON COLUMN event_name.killer IS 'Indicates whether this event kills the matter'");
    }

    /**
     * Create the classifier_type table.
     */
    private function createClassifierTypeTable(): void
    {
        if (Schema::hasTable('classifier_type')) {
            return;
        }

        Schema::create('classifier_type', function (Blueprint $table) {
            $table->string('code', 5)->primary();
            $table->jsonb('type')->default('{}');
            $table->boolean('main_display')->default(false);
            $table->string('for_category', 5)->nullable();
            $table->smallInteger('display_order')->default(127);
            $table->string('notes', 160)->nullable();
            $table->string('creator', 20)->nullable();
            $table->string('updater', 20)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // Note: Foreign key to matter_category will be added later

        DB::statement("COMMENT ON COLUMN classifier_type.main_display IS 'Indicates whether to display as main information'");
        DB::statement("COMMENT ON COLUMN classifier_type.for_category IS 'For showing in the pick-lists of only the selected category'");
    }

    /**
     * Create the template_classes table.
     */
    private function createTemplateClassesTable(): void
    {
        if (Schema::hasTable('template_classes')) {
            return;
        }

        Schema::create('template_classes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
            $table->string('notes', 160)->nullable();
            $table->string('default_role', 5)->nullable();
            $table->string('creator', 20)->nullable();
            $table->string('updater', 20)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        // Note: Foreign key to actor_role will be added later
    }
};
