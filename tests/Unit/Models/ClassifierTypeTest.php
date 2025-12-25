<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\ClassifierType;
use Tests\TestCase;

class ClassifierTypeTest extends TestCase
{
    /** @test */
    public function it_uses_code_as_primary_key()
    {
        $type = ClassifierType::first();

        if ($type) {
            $this->assertEquals('code', $type->getKeyName());
            $this->assertFalse($type->incrementing);
            $this->assertEquals('string', $type->getKeyType());
        } else {
            // Create a test type
            $type = ClassifierType::create([
                'code' => 'TEST',
                'type' => ['en' => 'Test Type'],
            ]);
            $this->assertEquals('TEST', $type->getKey());
        }
    }

    /** @test */
    public function it_has_translatable_type_name()
    {
        $type = new ClassifierType();

        $this->assertIsArray($type->translatable);
        $this->assertContains('type', $type->translatable);
    }

    /** @test */
    public function it_can_belong_to_a_category()
    {
        $category = Category::find('PAT');
        if (!$category) {
            $category = Category::factory()->create(['code' => 'PAT']);
        }

        $type = ClassifierType::create([
            'code' => 'TCAT',
            'type' => ['en' => 'Category Specific Type'],
            'for_category' => 'PAT',
        ]);

        $this->assertInstanceOf(Category::class, $type->category);
        $this->assertEquals('PAT', $type->category->code);
    }

    /** @test */
    public function it_can_have_null_category()
    {
        $type = ClassifierType::create([
            'code' => 'TALL',
            'type' => ['en' => 'All Categories Type'],
            'for_category' => null,
        ]);

        $this->assertNull($type->category);
    }

    /** @test */
    public function it_hides_audit_fields_on_serialization()
    {
        $type = ClassifierType::first() ?? ClassifierType::create([
            'code' => 'HID',
            'type' => ['en' => 'Hidden Fields Test'],
        ]);

        $array = $type->toArray();

        $this->assertArrayNotHasKey('creator', $array);
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('updated_at', $array);
        $this->assertArrayNotHasKey('updater', $array);
    }

    /** @test */
    public function it_guards_timestamp_fields()
    {
        $type = new ClassifierType();
        $guarded = $type->getGuarded();

        $this->assertContains('created_at', $guarded);
        $this->assertContains('updated_at', $guarded);
    }

    /** @test */
    public function it_uses_has_table_comments_trait()
    {
        $type = new ClassifierType();
        $traits = class_uses_recursive($type);

        $this->assertContains('App\Traits\HasTableComments', $traits);
    }

    /** @test */
    public function it_uses_has_translations_extended_trait()
    {
        $type = new ClassifierType();
        $traits = class_uses_recursive($type);

        $this->assertContains('App\Traits\HasTranslationsExtended', $traits);
    }
}
