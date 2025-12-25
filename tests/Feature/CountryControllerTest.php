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
        $admin = User::factory()->admin()->create();

        // Use unique test codes that won't conflict with seeded data
        Country::factory()->create([
            'iso' => 'X1',
            'name' => json_encode(['en' => 'Test Country X1']),
        ]);
        Country::factory()->create([
            'iso' => 'Y1',
            'name' => json_encode(['en' => 'Test Country Y1']),
        ]);

        $response = $this->actingAs($admin)->get('/countries?iso=X');

        $response->assertStatus(200);
        $response->assertViewHas('countries', function ($countries) {
            return $countries->count() === 1
                && $countries->first()->iso === 'X1';
        });
    }
}
