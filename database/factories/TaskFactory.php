<?php

namespace Database\Factories;

use App\Models\Event;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'trigger_id' => Event::factory(),
            'code' => 'REN',
            'due_date' => $this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d'),
            'assigned_to' => null,
            'done' => false,
            'done_date' => null,
            'detail' => json_encode(['en' => $this->faker->sentence(4), 'fr' => $this->faker->sentence(4)]),
            'notes' => null,
            'cost' => $this->faker->randomFloat(2, 50, 500),
            'fee' => $this->faker->randomFloat(2, 100, 1000),
            'rule_used' => null,
            'step' => null,
            'grace_period' => null,
            'invoice_step' => null,
        ];
    }

    /**
     * Renewal task
     */
    public function renewal(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'REN',
            'detail' => json_encode(['en' => 'Year '.$this->faker->numberBetween(2, 20), 'fr' => 'Année '.$this->faker->numberBetween(2, 20)]),
        ]);
    }

    /**
     * General deadline task
     */
    public function deadline(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'REP',  // REP = Respond, a valid task code in event_name
            'detail' => json_encode(['en' => 'Response deadline', 'fr' => 'Délai de réponse']),
        ]);
    }

    /**
     * Completed task
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'done' => true,
            'done_date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
        ]);
    }

    /**
     * Pending (open) task
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'done' => false,
            'done_date' => null,
        ]);
    }

    /**
     * Overdue task
     */
    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('-3 months', '-1 day')->format('Y-m-d'),
            'done' => false,
        ]);
    }

    /**
     * Task due soon
     */
    public function dueSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $this->faker->dateTimeBetween('+1 day', '+30 days')->format('Y-m-d'),
            'done' => false,
        ]);
    }

    /**
     * Task for a specific event
     */
    public function forEvent(Event $event): static
    {
        return $this->state(fn (array $attributes) => [
            'trigger_id' => $event->id,
        ]);
    }

    /**
     * Task assigned to a specific user
     */
    public function assignedTo(string $login): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_to' => $login,
        ]);
    }

    /**
     * Task due on a specific date
     */
    public function dueOn(string $date): static
    {
        return $this->state(fn (array $attributes) => [
            'due_date' => $date,
        ]);
    }

    /**
     * Task with specific cost and fee
     */
    public function withFees(float $cost, float $fee): static
    {
        return $this->state(fn (array $attributes) => [
            'cost' => $cost,
            'fee' => $fee,
        ]);
    }
}
