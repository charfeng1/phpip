<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        $iso = strtoupper($this->faker->unique()->lexify('??'));

        return [
            'iso' => $iso,
            'iso3' => strtoupper($this->faker->lexify('???')),
            'numcode' => $this->faker->numberBetween(1, 999),
            'name' => json_encode([
                'en' => $this->faker->country,
                'fr' => $this->faker->country,
            ]),
            'name_DE' => $this->faker->country,
            'name_FR' => $this->faker->country,
            'ep' => $this->faker->boolean(20),
            'wo' => $this->faker->boolean(30),
            'em' => $this->faker->boolean(20),
            'oa' => $this->faker->boolean(10),
        ];
    }

    /**
     * Create a country that is a regional office (EP, WO, etc.)
     */
    public function regional(): static
    {
        return $this->state(fn (array $attributes) => [
            'iso' => $this->faker->randomElement(['EP', 'WO', 'EM', 'OA']),
        ]);
    }
}
