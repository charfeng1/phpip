<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Matter;
use Illuminate\Database\Eloquent\Factories\Factory;

class EventFactory extends Factory
{
    protected $model = Event::class;

    public function definition(): array
    {
        return [
            'matter_id' => Matter::factory(),
            'code' => 'FIL',
            'event_date' => $this->faker->date(),
            'detail' => $this->faker->numerify('######'),
            'alt_matter_id' => null,
            'notes' => null,
        ];
    }

    /**
     * Filing event
     */
    public function filing(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'FIL',
            'event_date' => $this->faker->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d'),
            'detail' => $this->faker->numerify('##/###,###'),
        ]);
    }

    /**
     * Publication event
     */
    public function publication(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'PUB',
            'event_date' => $this->faker->dateTimeBetween('-3 years', '-6 months')->format('Y-m-d'),
            'detail' => $this->faker->numerify('US####/######'),
        ]);
    }

    /**
     * Grant event
     */
    public function grant(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'GRT',
            'event_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'detail' => $this->faker->numerify('US#,###,###'),
        ]);
    }

    /**
     * Registration event (for trademarks)
     */
    public function registration(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'REG',
            'event_date' => $this->faker->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'detail' => $this->faker->numerify('#######'),
        ]);
    }

    /**
     * Priority event
     */
    public function priority(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'PRI',
            'event_date' => $this->faker->dateTimeBetween('-6 years', '-4 years')->format('Y-m-d'),
        ]);
    }

    /**
     * National phase entry event
     */
    public function entry(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'ENT',
            'event_date' => $this->faker->dateTimeBetween('-4 years', '-2 years')->format('Y-m-d'),
        ]);
    }

    /**
     * Event for a specific matter
     */
    public function forMatter(Matter $matter): static
    {
        return $this->state(fn (array $attributes) => [
            'matter_id' => $matter->id,
        ]);
    }

    /**
     * Event with linked alternate matter (for priority claims)
     */
    public function withAltMatter(Matter $altMatter): static
    {
        return $this->state(fn (array $attributes) => [
            'alt_matter_id' => $altMatter->id,
        ]);
    }

    /**
     * Event on a specific date
     */
    public function onDate(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'event_date' => $date,
        ]);
    }
}
