<?php

namespace Database\Factories;

use App\Models\Rule;
use Illuminate\Database\Eloquent\Factories\Factory;

class RuleFactory extends Factory
{
    protected $model = Rule::class;

    public function definition(): array
    {
        return [
            'active' => true,
            'task' => 'REN',
            'trigger_event' => 'FIL',
            'for_category' => null,
            'for_country' => null,
            'for_origin' => null,
            'for_type' => null,
            'detail' => json_encode([
                'en' => $this->faker->sentence(4),
                'fr' => $this->faker->sentence(4),
            ]),
            'days' => 0,
            'months' => 12,
            'years' => 0,
            'end_of_month' => false,
            'recurring' => false,
            'use_priority' => false,
            'condition_event' => null,
            'abort_on' => null,
            'responsible' => null,
            'notes' => null,
            'fee' => null,
            'cost' => null,
            'use_before' => null,
            'clear_task' => false,
            'delete_task' => false,
        ];
    }

    /**
     * Active rule
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => true,
        ]);
    }

    /**
     * Inactive rule
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'active' => false,
        ]);
    }

    /**
     * Renewal rule
     */
    public function renewal(): static
    {
        return $this->state(fn (array $attributes) => [
            'task' => 'REN',
            'trigger_event' => 'FIL',
            'months' => 12,
            'recurring' => true,
        ]);
    }

    /**
     * Rule for a specific category
     */
    public function forCategory(string $categoryCode): static
    {
        return $this->state(fn (array $attributes) => [
            'for_category' => $categoryCode,
        ]);
    }

    /**
     * Rule for a specific country
     */
    public function forCountry(string $countryIso): static
    {
        return $this->state(fn (array $attributes) => [
            'for_country' => $countryIso,
        ]);
    }

    /**
     * Rule with condition event
     */
    public function withCondition(string $eventCode): static
    {
        return $this->state(fn (array $attributes) => [
            'condition_event' => $eventCode,
        ]);
    }

    /**
     * Rule that aborts on an event
     */
    public function abortsOn(string $eventCode): static
    {
        return $this->state(fn (array $attributes) => [
            'abort_on' => $eventCode,
        ]);
    }

    /**
     * Rule with specific deadline calculation
     */
    public function withDeadline(int $days = 0, int $months = 0, int $years = 0, bool $endOfMonth = false): static
    {
        return $this->state(fn (array $attributes) => [
            'days' => $days,
            'months' => $months,
            'years' => $years,
            'end_of_month' => $endOfMonth,
        ]);
    }

    /**
     * Recurring rule (generates multiple tasks)
     */
    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'recurring' => true,
        ]);
    }

    /**
     * Rule that uses priority date
     */
    public function usesPriority(): static
    {
        return $this->state(fn (array $attributes) => [
            'use_priority' => true,
        ]);
    }
}
