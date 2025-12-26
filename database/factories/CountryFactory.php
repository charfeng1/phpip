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
     * Uses digit prefixes (0-9) which are not used in real ISO-3166 alpha-2 codes.
     */
    private function generateTestIsoCode(int $counter): string
    {
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $prefixes = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];

        $prefixIndex = intdiv($counter, 26);
        $suffixIndex = $counter % 26;

        $prefix = $prefixes[$prefixIndex % 10];
        $suffix = $letters[$suffixIndex];

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
