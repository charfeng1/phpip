<?php

namespace Tests\Unit\Models;

use App\Models\EventClassLnk;
use Tests\TestCase;

class EventClassLnkTest extends TestCase
{
    /** @test */
    public function it_uses_correct_table_name()
    {
        $model = new EventClassLnk();

        $this->assertEquals('event_class_lnk', $model->getTable());
    }

    /** @test */
    public function it_has_guarded_empty_for_mass_assignment()
    {
        $model = new EventClassLnk();

        $this->assertEmpty($model->getGuarded());
    }

    /** @test */
    public function it_can_belong_to_a_template_class()
    {
        $model = new EventClassLnk();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $model->class()
        );
    }

    /** @test */
    public function class_relationship_uses_correct_foreign_key()
    {
        $model = new EventClassLnk();
        $relation = $model->class();

        $this->assertEquals('template_class_id', $relation->getForeignKeyName());
    }

    /** @test */
    public function it_extends_eloquent_model()
    {
        $model = new EventClassLnk();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $model);
    }

    /** @test */
    public function it_is_a_pivot_table_model()
    {
        // EventClassLnk represents a pivot table linking event names to template classes
        $model = new EventClassLnk();

        $this->assertEquals('event_class_lnk', $model->getTable());
    }

    /** @test */
    public function it_allows_mass_assignment_of_attributes()
    {
        // With empty guarded array, all attributes are mass assignable
        $model = new EventClassLnk();

        $this->assertTrue($model->isFillable('event_name_code'));
        $this->assertTrue($model->isFillable('template_class_id'));
    }
}
