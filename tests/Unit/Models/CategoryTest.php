<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Matter;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    /** @test */
    public function it_can_create_a_category()
    {
        $category = Category::factory()->create([
            'code' => 'TEST',
            'category' => ['en' => 'Test Category'],
        ]);

        $this->assertDatabaseHas('matter_category', [
            'code' => 'TEST',
        ]);
        $this->assertEquals('TEST', $category->code);
    }

    /** @test */
    public function it_uses_code_as_primary_key()
    {
        $category = Category::factory()->create(['code' => 'TST']);

        $this->assertEquals('code', $category->getKeyName());
        $this->assertEquals('TST', $category->getKey());
        $this->assertFalse($category->incrementing);
        $this->assertEquals('string', $category->getKeyType());
    }

    /** @test */
    public function it_has_translatable_category_name()
    {
        // Use existing PAT category from seeds instead of creating new one
        $category = Category::find('PAT') ?? Category::factory()->create([
            'code' => 'TRANS',
        ]);

        $this->assertIsArray($category->translatable);
        $this->assertContains('category', $category->translatable);
    }

    /** @test */
    public function it_can_have_many_matters()
    {
        $category = Category::find('PAT');
        if (! $category) {
            $category = Category::factory()->create(['code' => 'PAT']);
        }

        $matter = Matter::factory()->create(['category_code' => 'PAT']);

        $this->assertTrue($category->matters->contains($matter));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $category->matters());
    }

    /** @test */
    public function it_can_belong_to_display_with_category()
    {
        $parentCategory = Category::factory()->create(['code' => 'MAIN']);
        $childCategory = Category::factory()->create([
            'code' => 'SUB',
            'display_with' => 'MAIN',
        ]);

        $this->assertInstanceOf(Category::class, $childCategory->displayWithInfo);
        $this->assertEquals('MAIN', $childCategory->displayWithInfo->code);
    }

    /** @test */
    public function it_hides_audit_fields_on_serialization()
    {
        $category = Category::factory()->create(['code' => 'HID']);
        $array = $category->toArray();

        $this->assertArrayNotHasKey('creator', $array);
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('updated_at', $array);
        $this->assertArrayNotHasKey('updater', $array);
    }

    /** @test */
    public function it_guards_timestamp_fields()
    {
        $category = new Category;
        $guarded = $category->getGuarded();

        $this->assertContains('created_at', $guarded);
        $this->assertContains('updated_at', $guarded);
    }

    /** @test */
    public function categories_can_be_created_and_retrieved()
    {
        // Create standard categories using factory
        $pat = Category::firstOrCreate(
            ['code' => 'PAT'],
            ['category' => ['en' => 'Patent']]
        );
        $tm = Category::firstOrCreate(
            ['code' => 'TM'],
            ['category' => ['en' => 'Trademark']]
        );

        $this->assertEquals('PAT', $pat->code);
        $this->assertEquals('TM', $tm->code);

        // Verify retrieval works
        $this->assertInstanceOf(Category::class, Category::find('PAT'));
        $this->assertInstanceOf(Category::class, Category::find('TM'));
    }
}
