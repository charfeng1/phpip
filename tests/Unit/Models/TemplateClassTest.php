<?php

namespace Tests\Unit\Models;

use App\Models\TemplateClass;
use Tests\TestCase;

class TemplateClassTest extends TestCase
{
    /** @test */
    public function it_guards_timestamp_fields()
    {
        $model = new TemplateClass();
        $guarded = $model->getGuarded();

        $this->assertContains('created_at', $guarded);
        $this->assertContains('updated_at', $guarded);
    }

    /** @test */
    public function it_uses_has_table_comments_trait()
    {
        $model = new TemplateClass();
        $traits = class_uses_recursive($model);

        $this->assertContains('App\Traits\HasTableComments', $traits);
    }

    /** @test */
    public function it_can_belong_to_a_role()
    {
        $model = new TemplateClass();

        $relation = $model->role();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation
        );
    }

    /** @test */
    public function role_relationship_uses_correct_keys()
    {
        $model = new TemplateClass();
        $relation = $model->role();

        $this->assertEquals('default_role', $relation->getForeignKeyName());
        $this->assertEquals('code', $relation->getOwnerKeyName());
    }

    /** @test */
    public function it_has_many_to_many_relationship_with_rules()
    {
        $model = new TemplateClass();

        $relation = $model->rules();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $relation
        );
    }

    /** @test */
    public function rules_relationship_uses_correct_pivot_table()
    {
        $model = new TemplateClass();
        $relation = $model->rules();

        $this->assertEquals('rule_class_lnk', $relation->getTable());
    }

    /** @test */
    public function it_has_many_to_many_relationship_with_event_names()
    {
        $model = new TemplateClass();

        $relation = $model->eventNames();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsToMany::class,
            $relation
        );
    }

    /** @test */
    public function event_names_relationship_uses_correct_pivot_table()
    {
        $model = new TemplateClass();
        $relation = $model->eventNames();

        $this->assertEquals('event_class_lnk', $relation->getTable());
    }

    /** @test */
    public function it_extends_eloquent_model()
    {
        $model = new TemplateClass();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $model);
    }

    /** @test */
    public function it_uses_standard_table_name()
    {
        $model = new TemplateClass();

        $this->assertEquals('template_classes', $model->getTable());
    }

    /** @test */
    public function it_allows_mass_assignment_of_most_fields()
    {
        $model = new TemplateClass();

        // Only timestamps are guarded, so other fields should be fillable
        $this->assertTrue($model->isFillable('name'));
        $this->assertTrue($model->isFillable('default_role'));
    }
}
