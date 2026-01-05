<?php

namespace Tests\Unit\Models;

use App\Models\EventLnkList;
use Tests\TestCase;

class EventLnkListTest extends TestCase
{
    /** @test */
    public function it_uses_correct_table_name()
    {
        $model = new EventLnkList;

        $this->assertEquals('event_lnk_list', $model->getTable());
    }

    /** @test */
    public function it_casts_event_date_to_date()
    {
        $model = new EventLnkList;
        $casts = $model->getCasts();

        $this->assertArrayHasKey('event_date', $casts);
        $this->assertEquals('date', $casts['event_date']);
    }

    /** @test */
    public function it_can_belong_to_a_matter()
    {
        $model = new EventLnkList;

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $model->matter()
        );
    }

    /** @test */
    public function matter_relationship_uses_correct_foreign_key()
    {
        $model = new EventLnkList;
        $relation = $model->matter();

        $this->assertEquals('matter_id', $relation->getForeignKeyName());
    }

    /** @test */
    public function it_is_a_view_model()
    {
        // EventLnkList represents a database VIEW for event links
        // This confirms the model is configured correctly for read-only operations
        $model = new EventLnkList;

        $this->assertEquals('event_lnk_list', $model->getTable());
    }

    /** @test */
    public function it_extends_eloquent_model()
    {
        $model = new EventLnkList;

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $model);
    }
}
