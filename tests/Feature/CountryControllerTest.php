<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\User;
use Tests\TestCase;

class CountryControllerTest extends TestCase
{

    /** @test */
    public function countries_can_be_filtered_by_iso_prefix()
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin User']);

        Country::factory()->create([
            'iso' => 'US',
            'name' => json_encode(['en' => 'United States']),
        ]);
        Country::factory()->create([
            'iso' => 'FR',
            'name' => json_encode(['en' => 'France']),
        ]);

        $response = $this->actingAs($admin)->get('/countries?iso=U');

        $response->assertStatus(200);
        $response->assertViewHas('countries', function ($countries) {
            return $countries->count() === 1
                && $countries->first()->iso === 'US';
        });
    }
}
