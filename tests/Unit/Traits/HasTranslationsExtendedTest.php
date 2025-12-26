<?php

namespace Tests\Unit\Traits;

use App\Models\Category;
use App\Models\EventName;
use App\Traits\HasTranslationsExtended;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class HasTranslationsExtendedTest extends TestCase
{
    /** @test */
    public function it_normalizes_locale_to_base_language()
    {
        $category = Category::factory()->create(['code' => 'TRANS']);

        // Set translation with full locale
        $category->setTranslation('category', 'en_US', 'Patent');

        // Should be stored with base locale 'en'
        $translations = $category->getTranslations('category');

        $this->assertArrayHasKey('en', $translations);
        $this->assertEquals('Patent', $translations['en']);
    }

    /** @test */
    public function it_strips_locale_variants()
    {
        $category = Category::factory()->create(['code' => 'LOCV']);

        $category->setTranslation('category', 'en_GB', 'British Term');

        $translations = $category->getTranslations('category');

        // Should use 'en' not 'en_GB'
        $this->assertArrayHasKey('en', $translations);
        $this->assertArrayNotHasKey('en_GB', $translations);
    }

    /** @test */
    public function it_handles_simple_locale_codes()
    {
        $category = Category::factory()->create(['code' => 'SIMP']);

        $category->setTranslation('category', 'fr', 'Brevet');

        $translations = $category->getTranslations('category');

        $this->assertArrayHasKey('fr', $translations);
        $this->assertEquals('Brevet', $translations['fr']);
    }

    /** @test */
    public function category_uses_has_translations_extended()
    {
        $category = new Category();
        $traits = class_uses_recursive($category);

        $this->assertContains('App\Traits\HasTranslationsExtended', $traits);
    }

    /** @test */
    public function event_name_uses_has_translations_extended()
    {
        $eventName = new EventName();
        $traits = class_uses_recursive($eventName);

        $this->assertContains('App\Traits\HasTranslationsExtended', $traits);
    }

    /** @test */
    public function it_preserves_different_base_locales()
    {
        $category = Category::factory()->create(['code' => 'MULT']);

        $category->setTranslation('category', 'en', 'English Term');
        $category->setTranslation('category', 'fr', 'French Term');
        $category->setTranslation('category', 'de', 'German Term');

        $translations = $category->getTranslations('category');

        $this->assertEquals('English Term', $translations['en']);
        $this->assertEquals('French Term', $translations['fr']);
        $this->assertEquals('German Term', $translations['de']);
    }

    /** @test */
    public function set_translation_returns_model_instance()
    {
        $category = Category::factory()->create(['code' => 'CHAIN']);

        $result = $category->setTranslation('category', 'en', 'Test');

        $this->assertInstanceOf(Category::class, $result);
    }

    /** @test */
    public function it_overwrites_same_base_locale()
    {
        $category = Category::factory()->create(['code' => 'OVER']);

        $category->setTranslation('category', 'en_US', 'US Term');
        $category->setTranslation('category', 'en_GB', 'GB Term');

        $translations = $category->getTranslations('category');

        // Both should have written to 'en', so only GB Term should remain
        $this->assertEquals('GB Term', $translations['en']);
        $this->assertCount(1, array_filter(array_keys($translations), fn($k) => str_starts_with($k, 'en')));
    }

    /** @test */
    public function translatable_attribute_is_accessible()
    {
        $category = Category::factory()->create([
            'code' => 'ACCS',
            'category' => ['en' => 'Accessible Category'],
        ]);

        // Accessing via translatable should work
        $this->assertNotNull($category->category);
    }

    /** @test */
    public function it_handles_locale_with_script()
    {
        $category = Category::factory()->create(['code' => 'SCPT']);

        // Locale like zh_Hans_CN should become 'zh'
        $category->setTranslation('category', 'zh_Hans', 'Chinese Simplified');

        $translations = $category->getTranslations('category');

        $this->assertArrayHasKey('zh', $translations);
    }
}
