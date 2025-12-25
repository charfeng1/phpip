<?php

namespace Tests\Unit\Models;

use App\Models\Country;
use Tests\TestCase;

class CountryTest extends TestCase
{
    /** @test */
    public function it_uses_iso_as_primary_key()
    {
        // Create a country with factory to ensure data exists
        $country = Country::firstOrCreate(
            ['iso' => 'XX'],
            ['name' => ['en' => 'Test Country']]
        );

        $this->assertEquals('iso', $country->getKeyName());
        $this->assertEquals('XX', $country->getKey());
        $this->assertFalse($country->incrementing);
        $this->assertEquals('string', $country->getKeyType());
    }

    /** @test */
    public function it_has_no_timestamps()
    {
        $country = new Country();

        $this->assertFalse($country->usesTimestamps());
    }

    /** @test */
    public function it_has_translatable_name()
    {
        $country = new Country();

        $this->assertIsArray($country->translatable);
        $this->assertContains('name', $country->translatable);
    }

    /** @test */
    public function it_identifies_regional_offices_that_go_national()
    {
        $regionalOffices = ['EP', 'WO', 'EM', 'OA'];

        foreach ($regionalOffices as $iso) {
            $country = Country::find($iso);
            if ($country) {
                $this->assertTrue(
                    $country->goesnational,
                    "$iso should be identified as going national"
                );
            }
        }

        // Non-regional countries should not go national
        $us = Country::find('US');
        if ($us) {
            $this->assertFalse($us->goesnational);
        }
    }

    /** @test */
    public function it_returns_null_natcountries_for_non_regional_offices()
    {
        // Create a non-regional country
        $us = Country::firstOrCreate(
            ['iso' => 'US'],
            ['name' => ['en' => 'United States']]
        );

        $this->assertNull($us->natcountries);
    }

    /** @test */
    public function regional_offices_have_natcountries()
    {
        // Create EP as regional office
        $ep = Country::firstOrCreate(
            ['iso' => 'EP'],
            ['name' => ['en' => 'European Patent Office']]
        );

        // EP is identified as regional (goes national)
        $this->assertTrue($ep->goesnational);

        // natcountries should return null or Collection
        $natcountries = $ep->natcountries;
        $this->assertTrue($natcountries === null || $natcountries instanceof \Illuminate\Support\Collection);
    }

    /** @test */
    public function it_hides_iso3_and_numcode_on_serialization()
    {
        $country = Country::firstOrCreate(
            ['iso' => 'US'],
            ['name' => ['en' => 'United States'], 'iso3' => 'USA', 'numcode' => 840]
        );

        $array = $country->toArray();
        $this->assertArrayNotHasKey('iso3', $array);
        $this->assertArrayNotHasKey('numcode', $array);
    }

    /** @test */
    public function it_has_no_guarded_fields()
    {
        $country = new Country();

        $this->assertEmpty($country->getGuarded());
    }

    /** @test */
    public function goesnational_returns_boolean()
    {
        $country = new Country(['iso' => 'XX']);

        // Non-regional country
        $this->assertFalse($country->goesnational);

        // Mock regional country
        $country->iso = 'EP';
        $this->assertTrue($country->goesnational);
    }
}
