<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory
{
    protected $model = Country::class;

    /**
     * Counter for generating unique test country codes.
     */
    protected static int $testCodeCounter = 0;

    public function definition(): array
    {
        // Use X prefix with counter to avoid collision with real ISO codes
        // XA, XB, XC... XZ, then X0, X1... X9, then YA, YB...
        $counter = static::$testCodeCounter++;
        $iso = $this->generateTestIsoCode($counter);

        return [
            'iso' => $iso,
            'iso3' => 'T'.strtoupper($this->faker->lexify('??')),
            'numcode' => $this->faker->numberBetween(900, 999),
            'name' => json_encode([
                'en' => 'Test Country '.$counter,
                'fr' => 'Pays Test '.$counter,
            ]),
            'name_DE' => 'Testland '.$counter,
            'name_FR' => 'Pays Test '.$counter,
            'ep' => $this->faker->boolean(20),
            'wo' => $this->faker->boolean(30),
            'em' => $this->faker->boolean(20),
            'oa' => $this->faker->boolean(10),
        ];
    }

    /**
     * Generate a unique test ISO code that won't conflict with seed data.
     * Uses X and Y prefixes which are reserved/not used in real ISO codes.
     */
    private function generateTestIsoCode(int $counter): string
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $prefixes = ['X', 'Y', 'Z'];

        $prefixIndex = intdiv($counter, 36);
        $suffixIndex = $counter % 36;

        $prefix = $prefixes[$prefixIndex % 3] ?? 'X';
        $suffix = $letters[$suffixIndex] ?? 'A';

        return $prefix.$suffix;
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
