<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create Tier 4 relationship/junction tables.
 *
 * Tables created:
 * - matter_actor_lnk: Links actors to matters with roles
 * - classifier: Classifies matters with type-value pairs
 * - event_class_lnk: Links events to document template classes
 * - renewals_logs: Logs changes to renewal tasks
 *
 * Dependencies:
 * - matter_actor_lnk -> matter, actor, actor_role
 * - classifier -> matter, classifier_type, classifier_value
 * - event_class_lnk -> event_name, template_classes
 * - renewals_logs -> task
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createMatterActorLnkTable();
        $this->createClassifierTable();
        $this->createEventClassLnkTable();
        $this->createRenewalsLogsTable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('renewals_logs');
        Schema::dropIfExists('event_class_lnk');
        Schema::dropIfExists('classifier');
        Schema::dropIfExists('matter_actor_lnk');
    }

    /**
     * Create the matter_actor_lnk table.
     */
    private function createMatterActorLnkTable(): void
    {
        if (Schema::hasTable('matter_actor_lnk')) {
            return;
        }

        Schema::create('matter_actor_lnk', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('matter_id');
            $table->unsignedBigInteger('actor_id');
            $table->string('role', 5);
            $table->smallInteger('display_order')->default(1);
            $table->boolean('shared')->default(false);
            $table->string('actor_ref', 45)->nullable();
            $table->unsignedBigInteger('company_id')->nullable();
            $table->decimal('rate', 5, 2)->nullable();
            $table->date('date')->nullable();
            $table->char('creator', 16)->nullable();
            $table->char('updater', 16)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign keys
            $table->foreign('matter_id')->references('id')->on('matter')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('actor_id')->references('id')->on('actor')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('role')->references('code')->on('actor_role')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('company_id')->references('id')->on('actor')
                ->onDelete('set null')->onUpdate('cascade');
        });

        // Complex unique index with COALESCE for nullable company_id
        DB::statement('CREATE UNIQUE INDEX uqactor_link ON matter_actor_lnk (matter_id, actor_id, role, COALESCE(company_id, 0))');

        // Create indexes
        DB::statement('CREATE INDEX idx_mal_matter ON matter_actor_lnk(matter_id)');
        DB::statement('CREATE INDEX idx_mal_actor ON matter_actor_lnk(actor_id)');
        DB::statement('CREATE INDEX idx_mal_role ON matter_actor_lnk(role)');

        // Add comments
        DB::statement("COMMENT ON COLUMN matter_actor_lnk.shared IS 'Indicates whether this actor link is shared across the family'");
        DB::statement("COMMENT ON COLUMN matter_actor_lnk.actor_ref IS 'The actors reference for this matter'");
        DB::statement("COMMENT ON COLUMN matter_actor_lnk.company_id IS 'The company the actor is working for in this role'");
    }

    /**
     * Create the classifier table.
     */
    private function createClassifierTable(): void
    {
        if (Schema::hasTable('classifier')) {
            return;
        }

        Schema::create('classifier', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('matter_id');
            $table->string('type_code', 5);
            $table->text('value')->nullable();
            $table->binary('img')->nullable();
            $table->string('url', 256)->nullable();
            $table->unsignedBigInteger('value_id')->nullable();
            $table->smallInteger('display_order')->default(1);
            $table->unsignedBigInteger('lnk_matter_id')->nullable();
            $table->string('creator', 20)->nullable();
            $table->string('updater', 20)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign keys
            $table->foreign('matter_id')->references('id')->on('matter')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('type_code')->references('code')->on('classifier_type')
                ->onUpdate('cascade');
            $table->foreign('value_id')->references('id')->on('classifier_value')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('lnk_matter_id')->references('id')->on('matter')
                ->onDelete('cascade')->onUpdate('cascade');
        });

        // Create indexes
        DB::statement('CREATE INDEX idx_classifier_matter ON classifier(matter_id)');
        DB::statement('CREATE INDEX idx_classifier_type ON classifier(type_code)');

        // Add comments
        DB::statement("COMMENT ON COLUMN classifier.type_code IS 'Link to classifier_types'");
        DB::statement("COMMENT ON COLUMN classifier.value IS 'A free-text value used when classifier_values has no record linked to the classifier_types record'");
        DB::statement("COMMENT ON COLUMN classifier.url IS 'Display value as a link to the URL defined here'");
        DB::statement("COMMENT ON COLUMN classifier.value_id IS 'Links to the classifier_values table if it has a link to classifier_types'");
        DB::statement("COMMENT ON COLUMN classifier.lnk_matter_id IS 'Matter this case is linked to'");
    }

    /**
     * Create the event_class_lnk table.
     */
    private function createEventClassLnkTable(): void
    {
        if (Schema::hasTable('event_class_lnk')) {
            return;
        }

        Schema::create('event_class_lnk', function (Blueprint $table) {
            $table->id();
            $table->string('event_name_code', 5);
            $table->unsignedBigInteger('template_class_id');
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign keys
            $table->foreign('event_name_code')->references('code')->on('event_name')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('template_class_id')->references('id')->on('template_classes')
                ->onDelete('cascade')->onUpdate('cascade');

            // Unique constraint
            $table->unique(['event_name_code', 'template_class_id'], 'uq_event_class');
        });
    }

    /**
     * Create the renewals_logs table.
     */
    private function createRenewalsLogsTable(): void
    {
        if (Schema::hasTable('renewals_logs')) {
            return;
        }

        Schema::create('renewals_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->smallInteger('from_step')->nullable();
            $table->smallInteger('to_step')->nullable();
            $table->smallInteger('from_invoice_step')->nullable();
            $table->smallInteger('to_invoice_step')->nullable();
            $table->boolean('from_done')->nullable();
            $table->boolean('to_done')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->string('creator', 20)->nullable();

            // Foreign key
            $table->foreign('task_id')->references('id')->on('task')
                ->onDelete('cascade')->onUpdate('cascade');
        });

        DB::statement('CREATE INDEX idx_renewals_logs_task ON renewals_logs(task_id)');
    }
};
