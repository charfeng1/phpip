<?php

namespace Database\Factories;

use App\Enums\ClassifierType;
use App\Models\Classifier;
use App\Models\Matter;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClassifierFactory extends Factory
{
    protected $model = Classifier::class;

    public function definition(): array
    {
        return [
            'matter_id' => Matter::factory(),
            'type_code' => ClassifierType::TITLE->value,
            'value' => $this->faker->sentence(5),
        ];
    }

    /**
     * Create a title classifier
     */
    public function title(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_code' => ClassifierType::TITLE->value,
            'value' => $this->faker->sentence(6),
        ]);
    }

    /**
     * Create an official title classifier
     */
    public function officialTitle(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_code' => ClassifierType::TITLE_OFFICIAL->value,
            'value' => $this->faker->sentence(6),
        ]);
    }

    /**
     * Create an English title classifier
     */
    public function englishTitle(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_code' => ClassifierType::TITLE_EN->value,
            'value' => $this->faker->sentence(6),
        ]);
    }

    /**
     * Create a trademark name classifier
     */
    public function trademarkName(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_code' => ClassifierType::TRADEMARK_NAME->value,
            'value' => strtoupper($this->faker->word()),
        ]);
    }

    /**
     * Create a trademark class classifier
     */
    public function trademarkClass(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_code' => ClassifierType::TRADEMARK_CLASS->value,
            'value' => (string) $this->faker->numberBetween(1, 45),
        ]);
    }

    /**
     * Create an IPC class classifier
     */
    public function ipcClass(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_code' => ClassifierType::IPC_CLASS->value,
            'value' => $this->faker->regexify('[A-H][0-9]{2}[A-Z] [0-9]{1,2}/[0-9]{2}'),
        ]);
    }

    /**
     * Create a keyword classifier
     */
    public function keyword(): static
    {
        return $this->state(fn (array $attributes) => [
            'type_code' => ClassifierType::KEYWORD->value,
            'value' => $this->faker->word(),
        ]);
    }

    /**
     * Assign to a specific matter
     */
    public function forMatter(Matter $matter): static
    {
        return $this->state(fn (array $attributes) => [
            'matter_id' => $matter->id,
        ]);
    }
}
