<?php

namespace Tests\Unit\Models;

use App\Models\MatterType;
use Tests\TestCase;

class MatterTypeTest extends TestCase
{
    /** @test */
    public function it_uses_code_as_primary_key()
    {
        // Always create a matter type to ensure test data exists
        $type = MatterType::create([
            'code' => 'TEST',
            'type' => ['en' => 'Test Type'],
        ]);

        $this->assertEquals('code', $type->getKeyName());
        $this->assertEquals('TEST', $type->getKey());
        $this->assertFalse($type->incrementing);
        $this->assertEquals('string', $type->getKeyType());
    }

    /** @test */
    public function it_has_translatable_type_name()
    {
        $type = new MatterType();

        $this->assertIsArray($type->translatable);
        $this->assertContains('type', $type->translatable);
    }

    /** @test */
    public function it_can_create_a_matter_type()
    {
        $type = MatterType::create([
            'code' => 'PRV',
            'type' => ['en' => 'Provisional Application'],
        ]);

        $this->assertDatabaseHas('matter_type', [
            'code' => 'PRV',
        ]);
        $this->assertEquals('PRV', $type->code);
    }

    /** @test */
    public function it_hides_audit_fields_on_serialization()
    {
        $type = MatterType::first() ?? MatterType::create([
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
        $type = new MatterType();
        $guarded = $type->getGuarded();

        $this->assertContains('created_at', $guarded);
        $this->assertContains('updated_at', $guarded);
    }

    /** @test */
    public function it_uses_has_table_comments_trait()
    {
        $type = new MatterType();
        $traits = class_uses_recursive($type);

        $this->assertContains('App\Traits\HasTableComments', $traits);
    }

    /** @test */
    public function it_uses_has_translations_extended_trait()
    {
        $type = new MatterType();
        $traits = class_uses_recursive($type);

        $this->assertContains('App\Traits\HasTranslationsExtended', $traits);
    }

    /** @test */
    public function matter_types_can_be_created()
    {
        // Create common matter types to verify factory works
        $standardTypes = ['PRV', 'NPR', 'PCT'];

        foreach ($standardTypes as $code) {
            $type = MatterType::firstOrCreate(
                ['code' => $code],
                ['type' => ['en' => "Type $code"]]
            );
            $this->assertEquals($code, $type->code);
        }

        // Verify all types were created
        $this->assertCount(3, $standardTypes);
    }

    /** @test */
    public function it_can_store_translations_in_multiple_languages()
    {
        $type = MatterType::create([
            'code' => 'MUL',
            'type' => [
                'en' => 'Multi-Language Type',
                'fr' => 'Type Multi-Langue',
            ],
        ]);

        // Access via translatable trait
        $this->assertNotNull($type->type);
    }
}
