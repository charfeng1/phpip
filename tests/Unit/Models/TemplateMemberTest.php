<?php

namespace Tests\Unit\Models;

use App\Models\TemplateMember;
use Tests\TestCase;

class TemplateMemberTest extends TestCase
{
    /** @test */
    public function it_guards_timestamp_fields()
    {
        $model = new TemplateMember();
        $guarded = $model->getGuarded();

        $this->assertContains('created_at', $guarded);
        $this->assertContains('updated_at', $guarded);
    }

    /** @test */
    public function it_uses_has_table_comments_trait()
    {
        $model = new TemplateMember();
        $traits = class_uses_recursive($model);

        $this->assertContains('App\Traits\HasTableComments', $traits);
    }

    /** @test */
    public function it_can_belong_to_a_template_class()
    {
        $model = new TemplateMember();

        $relation = $model->class();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation
        );
    }

    /** @test */
    public function class_relationship_uses_correct_foreign_key()
    {
        $model = new TemplateMember();
        $relation = $model->class();

        $this->assertEquals('template_class_id', $relation->getForeignKeyName());
    }

    /** @test */
    public function it_extends_eloquent_model()
    {
        $model = new TemplateMember();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $model);
    }

    /** @test */
    public function it_uses_standard_table_name()
    {
        $model = new TemplateMember();

        $this->assertEquals('template_members', $model->getTable());
    }

    /** @test */
    public function it_allows_mass_assignment_of_most_fields()
    {
        $model = new TemplateMember();

        // Only timestamps are guarded, so other fields should be fillable
        $this->assertTrue($model->isFillable('summary'));
        $this->assertTrue($model->isFillable('body'));
        $this->assertTrue($model->isFillable('language'));
        $this->assertTrue($model->isFillable('category'));
        $this->assertTrue($model->isFillable('style'));
        $this->assertTrue($model->isFillable('format'));
        $this->assertTrue($model->isFillable('template_class_id'));
    }

    /** @test */
    public function it_has_timestamps_enabled()
    {
        $model = new TemplateMember();

        // By default, Eloquent models have timestamps enabled
        // TemplateMember doesn't explicitly disable them
        $this->assertTrue($model->usesTimestamps());
    }
}
