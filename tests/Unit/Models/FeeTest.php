<?php

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Country;
use App\Models\Fee;
use Tests\TestCase;

class FeeTest extends TestCase
{
    /** @test */
    public function it_can_create_a_fee()
    {
        $fee = Fee::create([
            'for_country' => 'US',
            'for_category' => 'PAT',
            'qt' => 1,
            'cost' => 100.00,
            'fee' => 500.00,
        ]);

        $this->assertDatabaseHas('fees', [
            'for_country' => 'US',
            'for_category' => 'PAT',
            'qt' => 1,
        ]);
    }

    /** @test */
    public function it_belongs_to_a_country()
    {
        // Ensure country exists
        Country::firstOrCreate(['iso' => 'US'], ['name' => ['en' => 'United States']]);

        $fee = Fee::create([
            'for_country' => 'US',
            'for_category' => 'PAT',
            'qt' => 2,
            'cost' => 150.00,
            'fee' => 750.00,
        ]);

        $country = $fee->country;

        $this->assertInstanceOf(Country::class, $country);
        $this->assertEquals('US', $country->iso);
    }

    /** @test */
    public function it_belongs_to_a_category()
    {
        // Ensure category exists
        Category::firstOrCreate(['code' => 'PAT'], ['category' => ['en' => 'Patent']]);

        $fee = Fee::create([
            'for_country' => 'EP',
            'for_category' => 'PAT',
            'qt' => 903,  // Use high unique value to avoid seed conflicts
            'cost' => 200.00,
            'fee' => 1000.00,
        ]);

        $category = $fee->category;

        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals('PAT', $category->code);
    }

    /** @test */
    public function it_can_have_origin_country()
    {
        // Ensure origin country exists
        Country::firstOrCreate(['iso' => 'EP'], ['name' => ['en' => 'European Patent Office']]);

        $fee = Fee::create([
            'for_country' => 'US',
            'for_category' => 'PAT',
            'for_origin' => 'EP',
            'qt' => 4,
            'cost' => 250.00,
            'fee' => 1250.00,
        ]);

        $origin = $fee->origin;

        $this->assertInstanceOf(Country::class, $origin);
        $this->assertEquals('EP', $origin->iso);
    }

    /** @test */
    public function it_can_have_null_origin()
    {
        $fee = Fee::create([
            'for_country' => 'DE',
            'for_category' => 'PAT',
            'for_origin' => null,
            'qt' => 905,  // Use high unique value to avoid seed conflicts
            'cost' => 300.00,
            'fee' => 1500.00,
        ]);

        $this->assertNull($fee->origin);
    }

    /** @test */
    public function it_hides_audit_fields_on_serialization()
    {
        $fee = Fee::create([
            'for_country' => 'FR',
            'for_category' => 'PAT',
            'qt' => 906,  // Use high unique value to avoid seed conflicts
            'cost' => 350.00,
            'fee' => 1750.00,
        ]);

        $array = $fee->toArray();

        $this->assertArrayNotHasKey('creator', $array);
        $this->assertArrayNotHasKey('created_at', $array);
        $this->assertArrayNotHasKey('updated_at', $array);
        $this->assertArrayNotHasKey('updater', $array);
    }

    /** @test */
    public function it_guards_id_and_timestamps()
    {
        $fee = new Fee();
        $guarded = $fee->getGuarded();

        $this->assertContains('id', $guarded);
        $this->assertContains('created_at', $guarded);
        $this->assertContains('updated_at', $guarded);
    }

    /** @test */
    public function it_uses_has_table_comments_trait()
    {
        $fee = new Fee();
        $traits = class_uses_recursive($fee);

        $this->assertContains('App\Traits\HasTableComments', $traits);
    }

    /** @test */
    public function it_stores_numeric_fee_values()
    {
        $fee = Fee::create([
            'for_country' => 'GB',
            'for_category' => 'PAT',
            'qt' => 7,
            'cost' => 123.45,
            'fee' => 678.90,
        ]);

        $this->assertEquals(123.45, $fee->cost);
        $this->assertEquals(678.90, $fee->fee);
    }
}
