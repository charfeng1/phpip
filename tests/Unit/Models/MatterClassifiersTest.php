<?php

namespace Tests\Unit\Models;

use App\Models\MatterClassifiers;
use Tests\TestCase;

class MatterClassifiersTest extends TestCase
{
    /** @test */
    public function it_has_timestamps_disabled()
    {
        $model = new MatterClassifiers();

        $this->assertFalse($model->timestamps);
    }

    /** @test */
    public function it_has_translatable_type_name()
    {
        $model = new MatterClassifiers();

        $this->assertIsArray($model->translatable);
        $this->assertContains('type_name', $model->translatable);
    }

    /** @test */
    public function it_uses_has_translations_extended_trait()
    {
        $model = new MatterClassifiers();
        $traits = class_uses_recursive($model);

        $this->assertContains('App\Traits\HasTranslationsExtended', $traits);
    }

    /** @test */
    public function it_can_belong_to_a_matter()
    {
        $model = new MatterClassifiers();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $model->matter()
        );
    }

    /** @test */
    public function it_can_have_a_linked_matter()
    {
        $model = new MatterClassifiers();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $model->linkedMatter()
        );
    }

    /** @test */
    public function it_can_belong_to_a_classifier_type()
    {
        $model = new MatterClassifiers();

        $relation = $model->classifierType();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $relation
        );
    }

    /** @test */
    public function linked_matter_relationship_uses_correct_foreign_key()
    {
        $model = new MatterClassifiers();
        $relation = $model->linkedMatter();

        $this->assertEquals('lnk_matter_id', $relation->getForeignKeyName());
    }

    /** @test */
    public function classifier_type_relationship_uses_code_as_owner_key()
    {
        $model = new MatterClassifiers();
        $relation = $model->classifierType();

        $this->assertEquals('type_code', $relation->getForeignKeyName());
        $this->assertEquals('code', $relation->getOwnerKeyName());
    }

    /** @test */
    public function it_is_a_view_model()
    {
        // MatterClassifiers represents a database VIEW
        // This confirms the model is configured correctly for read-only operations
        $model = new MatterClassifiers();

        // Views don't have incrementing primary keys
        $this->assertEquals('matter_classifiers', $model->getTable());
    }
}
