<?php

namespace Database\Factories;

use App\Enums\ActorRole;
use App\Models\Actor;
use App\Models\ActorPivot;
use App\Models\Matter;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActorPivotFactory extends Factory
{
    protected $model = ActorPivot::class;

    public function definition(): array
    {
        return [
            'matter_id' => Matter::factory(),
            'actor_id' => Actor::factory(),
            'role' => ActorRole::CLIENT->value,
            'display_order' => 1,
            'shared' => false,
        ];
    }

    /**
     * Create a client relationship
     */
    public function client(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => ActorRole::CLIENT->value,
        ]);
    }

    /**
     * Create an agent relationship
     */
    public function agent(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => ActorRole::AGENT->value,
        ]);
    }

    /**
     * Create an inventor relationship
     */
    public function inventor(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => ActorRole::INVENTOR->value,
        ]);
    }

    /**
     * Create an applicant relationship
     */
    public function applicant(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => ActorRole::APPLICANT->value,
        ]);
    }

    /**
     * Create an owner relationship
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => ActorRole::OWNER->value,
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

    /**
     * Assign a specific actor
     */
    public function forActor(Actor $actor): static
    {
        return $this->state(fn (array $attributes) => [
            'actor_id' => $actor->id,
        ]);
    }

    /**
     * Set display order
     */
    public function displayOrder(int $order): static
    {
        return $this->state(fn (array $attributes) => [
            'display_order' => $order,
        ]);
    }

    /**
     * Mark as shared with family
     */
    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'shared' => true,
        ]);
    }

    /**
     * Add an actor reference
     */
    public function withReference(string $ref): static
    {
        return $this->state(fn (array $attributes) => [
            'actor_ref' => $ref,
        ]);
    }
}
