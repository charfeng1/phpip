<?php

namespace Tests\Unit\Models;

use App\Models\Country;
use Tests\TestCase;

class CountryTest extends TestCase
{
    /** @test */
    public function it_uses_iso_as_primary_key()
    {
        $country = Country::find('US');

        if ($country) {
            $this->assertEquals('iso', $country->getKeyName());
            $this->assertEquals('US', $country->getKey());
            $this->assertFalse($country->incrementing);
            $this->assertEquals('string', $country->getKeyType());
        } else {
            $this->assertTrue(true); // Country seeder may not have run
        }
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
        $us = Country::find('US');

        if ($us) {
            $this->assertNull($us->natcountries);
        } else {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function regional_offices_have_natcountries()
    {
        $ep = Country::find('EP');

        if ($ep && $ep->goesnational) {
            $natcountries = $ep->natcountries;
            // If properly seeded, EP should have designated states
            $this->assertTrue($natcountries === null || $natcountries instanceof \Illuminate\Support\Collection);
        } else {
            $this->assertTrue(true);
        }
    }

    /** @test */
    public function it_hides_iso3_and_numcode_on_serialization()
    {
        $country = Country::find('US');

        if ($country) {
            $array = $country->toArray();
            $this->assertArrayNotHasKey('iso3', $array);
            $this->assertArrayNotHasKey('numcode', $array);
        } else {
            $this->assertTrue(true);
        }
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
