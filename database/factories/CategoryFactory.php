<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'category' => json_encode([
                'en' => $this->faker->word,
                'fr' => $this->faker->word,
            ]),
            'display_with' => null,
            'ref_prefix' => strtoupper($this->faker->lexify('?')),
        ];
    }

    /**
     * Patent category
     */
    public function patent(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'PAT',
            'category' => json_encode(['en' => 'Patent', 'fr' => 'Brevet']),
            'ref_prefix' => 'P',
        ]);
    }

    /**
     * Trademark category
     */
    public function trademark(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'TM',
            'category' => json_encode(['en' => 'Trademark', 'fr' => 'Marque']),
            'ref_prefix' => 'T',
        ]);
    }

    /**
     * Design category
     */
    public function design(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'DS',
            'category' => json_encode(['en' => 'Design', 'fr' => 'Dessin']),
            'ref_prefix' => 'D',
        ]);
    }
}
