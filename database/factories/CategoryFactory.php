<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /**
     * Counter for generating unique test category codes.
     */
    protected static int $testCodeCounter = 0;

    public function definition(): array
    {
        // Use TC prefix to avoid collision with real category codes
        $counter = static::$testCodeCounter++;
        $code = sprintf('TC%d', $counter);

        return [
            'code' => $code,
            'category' => json_encode([
                'en' => 'Test Category '.$counter,
                'fr' => 'Catégorie Test '.$counter,
            ]),
            'display_with' => null,
            'ref_prefix' => 'X',
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
