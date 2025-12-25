<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create Tier 3 business core tables.
 *
 * Tables created:
 * - matter: IP matters (patents, trademarks, etc.)
 * - event: Events associated with matters
 * - task_rules: Rules for automatic task creation
 * - task: Tasks/deadlines associated with events
 *
 * Dependencies:
 * - matter -> matter_category, country, matter_type
 * - event -> event_name, matter
 * - task_rules -> country, matter_category, matter_type, event_name
 * - task -> event, event_name, task_rules
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->createMatterTable();
        $this->createEventTable();
        $this->createTaskRulesTable();
        $this->createTaskTable();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop in reverse dependency order
        Schema::dropIfExists('task');
        Schema::dropIfExists('task_rules');
        Schema::dropIfExists('event');
        Schema::dropIfExists('matter');
    }

    /**
     * Create the matter table.
     */
    private function createMatterTable(): void
    {
        if (Schema::hasTable('matter')) {
            return;
        }

        Schema::create('matter', function (Blueprint $table) {
            $table->id();
            $table->string('category_code', 5);
            $table->string('caseref', 30);
            $table->char('country', 2);
            $table->char('origin', 2)->nullable();
            $table->string('type_code', 5)->nullable();
            $table->smallInteger('idx')->nullable();
            $table->string('suffix', 16)->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedBigInteger('container_id')->nullable();
            $table->string('responsible', 20)->nullable();
            $table->boolean('dead')->default(false);
            $table->string('alt_ref', 100)->nullable();
            $table->text('notes')->nullable();
            $table->date('expire_date')->nullable();
            $table->smallInteger('term_adjust')->default(0);
            $table->string('uid', 45)->nullable();
            $table->char('creator', 16)->nullable();
            $table->char('updater', 16)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign keys
            $table->foreign('category_code')->references('code')->on('matter_category')
                ->onUpdate('cascade');
            $table->foreign('country')->references('iso')->on('country')
                ->onUpdate('cascade');
            $table->foreign('origin')->references('iso')->on('country')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('type_code')->references('code')->on('matter_type')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('parent_id')->references('id')->on('matter')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('container_id')->references('id')->on('matter')
                ->onDelete('set null')->onUpdate('cascade');
        });

        // Create unique index
        DB::statement('CREATE UNIQUE INDEX uqmatter ON matter(caseref, country, origin, type_code, idx)');

        // Create indexes
        DB::statement('CREATE INDEX idx_matter_category ON matter(category_code)');
        DB::statement('CREATE INDEX idx_matter_caseref ON matter(caseref)');
        DB::statement('CREATE INDEX idx_matter_country ON matter(country)');
        DB::statement('CREATE INDEX idx_matter_responsible ON matter(responsible)');

        // Add comments
        DB::statement("COMMENT ON COLUMN matter.caseref IS 'The case reference, typically the client reference'");
        DB::statement("COMMENT ON COLUMN matter.origin IS 'For claiming priority or origin of national phase'");
        DB::statement("COMMENT ON COLUMN matter.idx IS 'Index for distinguishing between matters with the same caseref-country pair'");
        DB::statement("COMMENT ON COLUMN matter.suffix IS 'Free suffix after idx in UID'");
        DB::statement("COMMENT ON COLUMN matter.parent_id IS 'Parent matter for continuation/divisional'");
        DB::statement("COMMENT ON COLUMN matter.container_id IS 'Container matter for shared data (actors, classifiers)'");
        DB::statement("COMMENT ON COLUMN matter.dead IS 'Indicates whether the matter is dead (abandoned, lapsed, etc.)'");
        DB::statement("COMMENT ON COLUMN matter.alt_ref IS 'Alternative reference'");
        DB::statement("COMMENT ON COLUMN matter.expire_date IS 'Calculated expiry date'");
        DB::statement("COMMENT ON COLUMN matter.term_adjust IS 'Patent term adjustment in days'");
    }

    /**
     * Create the event table.
     */
    private function createEventTable(): void
    {
        if (Schema::hasTable('event')) {
            return;
        }

        Schema::create('event', function (Blueprint $table) {
            $table->id();
            $table->string('code', 5);
            $table->unsignedBigInteger('matter_id');
            $table->date('event_date')->nullable();
            $table->unsignedBigInteger('alt_matter_id')->nullable();
            $table->string('detail', 45)->nullable();
            $table->string('notes', 150)->nullable();
            $table->char('creator', 16)->nullable();
            $table->char('updater', 16)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign keys
            $table->foreign('code')->references('code')->on('event_name')
                ->onDelete('restrict')->onUpdate('cascade');
            $table->foreign('matter_id')->references('id')->on('matter')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('alt_matter_id')->references('id')->on('matter')
                ->onDelete('set null')->onUpdate('cascade');

            // Unique constraint
            $table->unique(['matter_id', 'code', 'event_date', 'alt_matter_id'], 'uqevent');
        });

        // Create indexes
        DB::statement('CREATE INDEX idx_event_code ON event(code)');
        DB::statement('CREATE INDEX idx_event_date ON event(event_date)');
        DB::statement('CREATE INDEX idx_event_detail ON event(detail)');

        // Add comments
        DB::statement("COMMENT ON COLUMN event.code IS 'Link to event_names table'");
        DB::statement("COMMENT ON COLUMN event.alt_matter_id IS 'Essentially for priority claims. ID of prior patent this event refers to'");
        DB::statement("COMMENT ON COLUMN event.detail IS 'Numbers or short comments'");
    }

    /**
     * Create the task_rules table.
     */
    private function createTaskRulesTable(): void
    {
        if (Schema::hasTable('task_rules')) {
            return;
        }

        Schema::create('task_rules', function (Blueprint $table) {
            $table->id();
            $table->boolean('active')->default(true);
            $table->string('for_category', 5)->nullable();
            $table->char('for_country', 2)->nullable();
            $table->char('for_origin', 2)->nullable();
            $table->string('for_type', 5)->nullable();
            $table->string('task', 5);
            $table->jsonb('detail')->nullable();
            $table->smallInteger('days')->default(0);
            $table->smallInteger('months')->default(0);
            $table->smallInteger('years')->default(0);
            $table->boolean('recurring')->default(false);
            $table->boolean('end_of_month')->default(false);
            $table->string('abort_on', 5)->nullable();
            $table->string('condition_event', 5)->nullable();
            $table->boolean('use_priority')->default(false);
            $table->date('use_before')->nullable();
            $table->date('use_after')->nullable();
            $table->decimal('cost', 6, 2)->nullable();
            $table->decimal('fee', 6, 2)->nullable();
            $table->char('currency', 3)->default('EUR');
            $table->string('trigger_event', 5);
            $table->boolean('clear_task')->default(false);
            $table->boolean('delete_task')->default(false);
            $table->string('responsible', 20)->nullable();
            $table->string('notes', 160)->nullable();
            $table->char('creator', 16)->nullable();
            $table->char('updater', 16)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign keys
            $table->foreign('for_category')->references('code')->on('matter_category')
                ->onUpdate('cascade');
            $table->foreign('for_country')->references('iso')->on('country')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('for_origin')->references('iso')->on('country')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('for_type')->references('code')->on('matter_type')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('task')->references('code')->on('event_name')
                ->onUpdate('cascade');
            $table->foreign('abort_on')->references('code')->on('event_name')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('condition_event')->references('code')->on('event_name')
                ->onDelete('set null')->onUpdate('cascade');
            $table->foreign('trigger_event')->references('code')->on('event_name')
                ->onUpdate('cascade');
        });

        // Add comments
        DB::statement("COMMENT ON COLUMN task_rules.active IS 'Indicates whether the rule is active'");
        DB::statement("COMMENT ON COLUMN task_rules.for_category IS 'Apply to this category'");
        DB::statement("COMMENT ON COLUMN task_rules.for_country IS 'Apply to this country only'");
        DB::statement("COMMENT ON COLUMN task_rules.for_origin IS 'Apply to this origin only'");
        DB::statement("COMMENT ON COLUMN task_rules.for_type IS 'Apply to this type only'");
        DB::statement("COMMENT ON COLUMN task_rules.task IS 'The task to create'");
        DB::statement("COMMENT ON COLUMN task_rules.days IS 'Days to add to trigger date'");
        DB::statement("COMMENT ON COLUMN task_rules.months IS 'Months to add to trigger date'");
        DB::statement("COMMENT ON COLUMN task_rules.years IS 'Years to add to trigger date'");
        DB::statement("COMMENT ON COLUMN task_rules.recurring IS 'Is this a recurring task (renewals)'");
        DB::statement("COMMENT ON COLUMN task_rules.end_of_month IS 'Set due date to end of month'");
        DB::statement("COMMENT ON COLUMN task_rules.abort_on IS 'Abort task creation if this event exists'");
        DB::statement("COMMENT ON COLUMN task_rules.condition_event IS 'Only create task if this event exists'");
        DB::statement("COMMENT ON COLUMN task_rules.use_priority IS 'Use earliest priority date for calculation'");
        DB::statement("COMMENT ON COLUMN task_rules.trigger_event IS 'The event that triggers this task'");
        DB::statement("COMMENT ON COLUMN task_rules.clear_task IS 'Mark matching task as done instead of creating'");
        DB::statement("COMMENT ON COLUMN task_rules.delete_task IS 'Delete matching task instead of creating'");
    }

    /**
     * Create the task table.
     */
    private function createTaskTable(): void
    {
        if (Schema::hasTable('task')) {
            return;
        }

        Schema::create('task', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trigger_id');
            $table->string('code', 5);
            $table->date('due_date')->nullable();
            $table->boolean('done')->default(false);
            $table->date('done_date')->nullable();
            $table->string('assigned_to', 20)->nullable();
            $table->jsonb('detail')->nullable();
            $table->text('notes')->nullable();
            $table->smallInteger('step')->nullable();
            $table->boolean('grace_period')->nullable();
            $table->smallInteger('invoice_step')->nullable();
            $table->decimal('cost', 10, 2)->nullable();
            $table->decimal('fee', 10, 2)->nullable();
            $table->char('currency', 3)->nullable();
            $table->unsignedBigInteger('rule_used')->nullable();
            $table->char('creator', 16)->nullable();
            $table->char('updater', 16)->nullable();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            // Foreign keys
            $table->foreign('trigger_id')->references('id')->on('event')
                ->onDelete('cascade')->onUpdate('cascade');
            $table->foreign('code')->references('code')->on('event_name')
                ->onUpdate('cascade');
            $table->foreign('rule_used')->references('id')->on('task_rules')
                ->onDelete('set null')->onUpdate('cascade');
        });

        // Create indexes
        DB::statement('CREATE INDEX idx_task_trigger ON task(trigger_id)');
        DB::statement('CREATE INDEX idx_task_code ON task(code)');
        DB::statement('CREATE INDEX idx_task_due_date ON task(due_date)');
        DB::statement('CREATE INDEX idx_task_done ON task(done)');

        // Add comments
        DB::statement("COMMENT ON COLUMN task.trigger_id IS 'The event that triggered this task'");
        DB::statement("COMMENT ON COLUMN task.code IS 'Task type code'");
        DB::statement("COMMENT ON COLUMN task.done IS 'Task completion status'");
        DB::statement("COMMENT ON COLUMN task.step IS 'Workflow step'");
        DB::statement("COMMENT ON COLUMN task.invoice_step IS 'Invoicing step'");
        DB::statement("COMMENT ON COLUMN task.rule_used IS 'The rule that created this task'");
    }
};
