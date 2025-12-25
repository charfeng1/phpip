<?php

namespace Tests\Unit\Models;

use App\Models\MatterActors;
use Tests\TestCase;

class MatterActorsTest extends TestCase
{
    /** @test */
    public function it_has_timestamps_disabled()
    {
        $model = new MatterActors();

        $this->assertFalse($model->timestamps);
    }

    /** @test */
    public function it_has_translatable_role_name()
    {
        $model = new MatterActors();

        $this->assertIsArray($model->translatable);
        $this->assertContains('role_name', $model->translatable);
    }

    /** @test */
    public function it_uses_has_translations_extended_trait()
    {
        $model = new MatterActors();
        $traits = class_uses_recursive($model);

        $this->assertContains('App\Traits\HasTranslationsExtended', $traits);
    }

    /** @test */
    public function it_can_belong_to_a_matter()
    {
        $model = new MatterActors();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $model->matter()
        );
    }

    /** @test */
    public function it_can_belong_to_an_actor()
    {
        $model = new MatterActors();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $model->actor()
        );
    }

    /** @test */
    public function it_can_belong_to_a_role()
    {
        $model = new MatterActors();

        $relation = $model->role();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation
        );
    }

    /** @test */
    public function it_can_belong_to_a_company()
    {
        $model = new MatterActors();

        $relation = $model->company();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation
        );
    }

    /** @test */
    public function role_relationship_uses_role_code_as_foreign_key()
    {
        $model = new MatterActors();
        $relation = $model->role();

        $this->assertEquals('role_code', $relation->getForeignKeyName());
    }

    /** @test */
    public function company_relationship_uses_company_id_as_foreign_key()
    {
        $model = new MatterActors();
        $relation = $model->company();

        $this->assertEquals('company_id', $relation->getForeignKeyName());
    }

    /** @test */
    public function it_is_a_view_model()
    {
        // MatterActors represents a database VIEW
        // This confirms the model is configured correctly for read-only operations
        $model = new MatterActors();

        $this->assertEquals('matter_actors', $model->getTable());
    }

    /** @test */
    public function it_extends_eloquent_model()
    {
        $model = new MatterActors();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Model::class, $model);
    }
}
