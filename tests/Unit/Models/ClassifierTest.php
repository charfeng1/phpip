<?php

namespace Tests\Unit\Models;

use App\Models\Classifier;
use App\Models\ClassifierType;
use App\Models\Matter;
use Tests\TestCase;

class ClassifierTest extends TestCase
{
    /** @test */
    public function it_can_create_a_classifier()
    {
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test Title',
        ]);

        $this->assertDatabaseHas('classifier', [
            'matter_id' => $matter->id,
            'value' => 'Test Title',
        ]);
    }

    /** @test */
    public function it_belongs_to_a_matter()
    {
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test',
        ]);

        $this->assertInstanceOf(Matter::class, $classifier->matter);
        $this->assertEquals($matter->id, $classifier->matter->id);
    }

    /** @test */
    public function it_belongs_to_a_classifier_type()
    {
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test',
        ]);

        $this->assertInstanceOf(ClassifierType::class, $classifier->type);
    }

    /** @test */
    public function it_can_link_to_another_matter()
    {
        $matter = Matter::factory()->create();
        $linkedMatter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'LNK',
            'type' => ['en' => 'Link'],
        ]);

        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'lnk_matter_id' => $linkedMatter->id,
        ]);

        $this->assertInstanceOf(Matter::class, $classifier->linkedMatter);
        $this->assertEquals($linkedMatter->id, $classifier->linkedMatter->id);
    }

    /** @test */
    public function it_touches_parent_matter_on_update()
    {
        $touches = (new Classifier())->getTouchedRelations();

        $this->assertContains('matter', $touches);
    }

    /** @test */
    public function it_uses_auditable_trait()
    {
        $classifier = new Classifier();
        $traits = class_uses_recursive($classifier);

        $this->assertContains('App\Traits\Auditable', $traits);
    }

    /** @test */
    public function it_excludes_timestamps_from_audit()
    {
        $classifier = new Classifier();
        $reflection = new \ReflectionClass($classifier);

        // The model should have auditExclude property
        $this->assertTrue(
            $reflection->hasProperty('auditExclude'),
            'Classifier should have auditExclude property'
        );

        $property = $reflection->getProperty('auditExclude');
        $property->setAccessible(true);
        $auditExclude = $property->getValue($classifier);

        $this->assertContains('created_at', $auditExclude);
        $this->assertContains('updated_at', $auditExclude);
    }

    /** @test */
    public function it_hides_audit_fields_on_serialization()
    {
        $matter = Matter::factory()->create();
        $classifierType = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'TIT',
            'type' => ['en' => 'Title'],
        ]);

        $classifier = Classifier::create([
            'matter_id' => $matter->id,
            'type_code' => $classifierType->code,
            'value' => 'Test',
        ]);

        $array = $classifier->toArray();

        $this->assertArrayNotHasKey('creator', $array);
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('updated_at', $array);
        $this->assertArrayNotHasKey('updater', $array);
    }

    /** @test */
    public function it_guards_id_and_timestamps()
    {
        $classifier = new Classifier();
        $guarded = $classifier->getGuarded();

        $this->assertContains('id', $guarded);
        $this->assertContains('created_at', $guarded);
        $this->assertContains('updated_at', $guarded);
    }
}
