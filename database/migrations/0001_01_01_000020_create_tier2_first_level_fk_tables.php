<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create Tier 2 tables with first-level foreign key dependencies.
 *
 * Tables created:
 * - actor: People and companies (clients, agents, inventors, etc.)
 * - classifier_value: Predefined values for classifiers
 * - template_members: Individual document templates
 * - fees: Renewal and official fees by country/category
 * - default_actor: Default actor assignments
 *
 * Dependencies:
 * - actor -> country, actor_role
 * - classifier_value -> classifier_type
 * - template_members -> template_classes
 * - fees -> country, matter_category
 * - default_actor -> actor, actor_role, country, matter_category
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createActorTable();
        $this->createClassifierValueTable();
        $this->createTemplateMembersTable();
        $this->createFeesTable();
        $this->createDefaultActorTable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse dependency order
        Schema::dropIfExists('default_actor');
        Schema::dropIfExists('fees');
        Schema::dropIfExists('template_members');
        Schema::dropIfExists('classifier_value');
        Schema::dropIfExists('actor');
    }

    /**
     * Create the actor table.
     */
    private function createActorTable(): void
    {
        if (Schema::hasTable('actor')) {
            return;
        }

        Schema::create('actor', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('first_name', 60)->nullable();
            $table->string('display_name', 30)->unique()->nullable();
            $table->char('login', 16)->unique()->nullable();
            $table->string('password', 60)->nullable();
            $table->string('default_role', 5)->nullable();
            $table->string('function', 45)->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->unsignedBigInteger('site_id')->nullable();
            $table->boolean('phy_person')->default(true);
            $table->char('nationality', 2)->nullable();
            $table->char('language', 2)->nullable();
            $table->boolean('small_entity')->default(false);
            $table->string('address', 256)->nullable();
            $table->char('country', 2)->nullable();
            $table->string('address_mailing', 256)->nullable();
            $table->char('country_mailing', 2)->nullable();
            $table->string('address_billing', 256)->nullable();
            $table->char('country_billing', 2)->nullable();
            $table->string('email', 45)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('fax', 20)->nullable();
            $table->string('url', 256)->nullable();
            $table->string('legal_form', 60)->nullable();
            $table->string('registration_no', 20)->nullable();
            $table->boolean('warn')->default(false);
            $table->decimal('ren_discount', 8, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('VAT_number', 45)->nullable();
            $table->char('creator', 16)->nullable();
            $table->char('updater', 16)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->string('remember_token', 100)->nullable();

            // Foreign keys
            $table->foreign('default_role')->references('code')->on('actor_role')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('parent_id')->references('id')->on('actor')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('company_id')->references('id')->on('actor')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('site_id')->references('id')->on('actor')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('nationality')->references('iso')->on('country')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('country')->references('iso')->on('country')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('country_mailing')->references('iso')->on('country')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('country_billing')->references('iso')->on('country')
                ->onDelete('set null')->onUpdate('cascade');
        });

        // Add index
        DB::statement('CREATE INDEX idx_actor_name ON actor(name)');

        // Add comments
        DB::statement("COMMENT ON COLUMN actor.name IS 'Family name or company name'");
        DB::statement("COMMENT ON COLUMN actor.first_name IS 'plus middle names, if required'");
        DB::statement("COMMENT ON COLUMN actor.display_name IS 'The name displayed in the interface, if not null'");
        DB::statement("COMMENT ON COLUMN actor.login IS 'Database user login if not null'");
        DB::statement("COMMENT ON COLUMN actor.default_role IS 'Link to actor_role table. A same actor can have different roles - this is the default role of the actor.'");
        DB::statement("COMMENT ON COLUMN actor.parent_id IS 'Parent company of this company (another actor), where applicable'");
        DB::statement("COMMENT ON COLUMN actor.company_id IS 'Mainly for inventors and contacts. ID of the actor company or employer'");
        DB::statement("COMMENT ON COLUMN actor.site_id IS 'Mainly for inventors and contacts. ID of the actor company site'");
        DB::statement("COMMENT ON COLUMN actor.phy_person IS 'Physical person or not'");
        DB::statement("COMMENT ON COLUMN actor.small_entity IS 'Small entity status used in a few countries (FR, US)'");
        DB::statement("COMMENT ON COLUMN actor.address IS 'Main address: street, zip and city'");
        DB::statement("COMMENT ON COLUMN actor.warn IS 'The actor will be displayed in red in the matter view when set'");
    }

    /**
     * Create the classifier_value table.
     */
    private function createClassifierValueTable(): void
    {
        if (Schema::hasTable('classifier_value')) {
            return;
        }

        Schema::create('classifier_value', function (Blueprint $table) {
            $table->id();
            $table->string('value', 160);
            $table->string('type_code', 5)->nullable();
            $table->string('notes', 255)->nullable();
            $table->string('creator', 20)->nullable();
            $table->string('updater', 20)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign key
            $table->foreign('type_code')->references('code')->on('classifier_type')
                ->onDelete('set null')->onUpdate('cascade');

            // Unique constraint
            $table->unique(['value', 'type_code'], 'uqclvalue');
        });

        DB::statement("COMMENT ON COLUMN classifier_value.type_code IS 'Restrict this classifier name to the classifier type identified here'");
    }

    /**
     * Create the template_members table.
     */
    private function createTemplateMembersTable(): void
    {
        if (Schema::hasTable('template_members')) {
            return;
        }

        Schema::create('template_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->char('language', 2)->nullable();
            $table->string('style', 45)->nullable();
            $table->string('category', 45)->nullable();
            $table->string('format', 45)->nullable();
            $table->string('summary', 160)->nullable();
            $table->text('subject')->nullable();
            $table->text('body')->nullable();
            $table->string('notes', 160)->nullable();
            $table->string('creator', 20)->nullable();
            $table->string('updater', 20)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign key
            $table->foreign('class_id')->references('id')->on('template_classes')
                ->onDelete('cascade')->onUpdate('cascade');
        });

        DB::statement('CREATE INDEX idx_template_members_class ON template_members(class_id)');
    }

    /**
     * Create the fees table.
     */
    private function createFeesTable(): void
    {
        if (Schema::hasTable('fees')) {
            return;
        }

        Schema::create('fees', function (Blueprint $table) {
            $table->id();
            $table->char('for_country', 2);
            $table->string('for_category', 5);
            $table->char('for_origin', 2)->nullable();
            $table->integer('qt');
            $table->date('use_before')->nullable();
            $table->date('use_after')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('fee', 10, 2)->nullable();
            $table->decimal('cost_reduced', 10, 2)->nullable();
            $table->decimal('fee_reduced', 10, 2)->nullable();
            $table->decimal('cost_sup', 10, 2)->nullable();
            $table->decimal('fee_sup', 10, 2)->nullable();
            $table->decimal('cost_sup_reduced', 10, 2)->nullable();
            $table->decimal('fee_sup_reduced', 10, 2)->nullable();
            $table->char('currency', 3)->default('EUR');
            $table->string('notes', 160)->nullable();
            $table->string('creator', 20)->nullable();
            $table->string('updater', 20)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign keys
            $table->foreign('for_country')->references('iso')->on('country')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('for_category')->references('code')->on('matter_category')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('for_origin')->references('iso')->on('country')
                ->onDelete('cascade')->onUpdate('cascade');
        });

        // Complex unique constraint with COALESCE
        DB::statement("CREATE UNIQUE INDEX uqfees ON fees (for_country, for_category, qt, COALESCE(use_before, '9999-12-31'::DATE))");

        // Add comments
        DB::statement("COMMENT ON COLUMN fees.qt IS 'Quantity (typically renewal year)'");
        DB::statement("COMMENT ON COLUMN fees.cost_reduced IS 'Reduced cost for small entities'");
        DB::statement("COMMENT ON COLUMN fees.fee_reduced IS 'Reduced fee for small entities'");
        DB::statement("COMMENT ON COLUMN fees.cost_sup IS 'Supplemental cost (late payment)'");
        DB::statement("COMMENT ON COLUMN fees.fee_sup IS 'Supplemental fee (late payment)'");
    }

    /**
     * Create the default_actor table.
     */
    private function createDefaultActorTable(): void
    {
        if (Schema::hasTable('default_actor')) {
            return;
        }

        Schema::create('default_actor', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_id');
            $table->string('role', 5);
            $table->string('for_category', 5)->nullable();
            $table->char('for_country', 2)->nullable();
            $table->unsignedBigInteger('for_client')->nullable();
            $table->boolean('shared')->default(false);
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign keys
            $table->foreign('actor_id')->references('id')->on('actor')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('role')->references('code')->on('actor_role')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('for_category')->references('code')->on('matter_category')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('for_country')->references('iso')->on('country')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('for_client')->references('id')->on('actor')
                ->onDelete('cascade')->onUpdate('cascade');
        });

        // Add indexes
        DB::statement('CREATE INDEX idx_default_actor_actor ON default_actor(actor_id)');
        DB::statement('CREATE INDEX idx_default_actor_role ON default_actor(role)');
        DB::statement('CREATE INDEX idx_default_actor_country ON default_actor(for_country)');
        DB::statement('CREATE INDEX idx_default_actor_client ON default_actor(for_client)');
    }
};
