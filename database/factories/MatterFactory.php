<?php

namespace Database\Factories;

use App\Models\Matter;
use Illuminate\Database\Eloquent\Factories\Factory;

class MatterFactory extends Factory
{
    protected $model = Matter::class;

    public function definition(): array
    {
        $caseref = strtoupper($this->faker->lexify('????')).$this->faker->numberBetween(1000, 9999);

        return [
            'category_code' => 'PAT',
            'caseref' => $caseref,
            'country' => 'US',
            'origin' => null,
            'type_code' => null,
            'idx' => null,
            'suffix' => null,
            'container_id' => null,
            'parent_id' => null,
            'responsible' => null,
            'dead' => false,
            'notes' => null,
            'expire_date' => null,
            'term_adjust' => 0,
            'alt_ref' => null,
        ];
    }

    /**
     * Refresh model after creation to get trigger-computed uid
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Matter $matter) {
            // Refresh to get the uid computed by database trigger
            // Format: caseref || country || origin || type_code || idx
            $matter->refresh();
        });
    }

    /**
     * Patent matter
     */
    public function patent(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_code' => 'PAT',
        ]);
    }

    /**
     * Trademark matter
     */
    public function trademark(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_code' => 'TM',
        ]);
    }

    /**
     * Design matter
     */
    public function design(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_code' => 'DS',
        ]);
    }

    /**
     * Matter in a specific country
     */
    public function inCountry(string $country): static
    {
        return $this->state(fn (array $attributes) => [
            'country' => $country,
        ]);
    }

    /**
     * Matter with a specific origin
     */
    public function withOrigin(string $origin): static
    {
        return $this->state(fn (array $attributes) => [
            'origin' => $origin,
        ]);
    }

    /**
     * Container matter (family container)
     */
    public function asContainer(): static
    {
        return $this->state(fn (array $attributes) => [
            'container_id' => null,
        ]);
    }

    /**
     * Family member matter (belongs to a container)
     */
    public function asFamilyMember(Matter $container): static
    {
        return $this->state(fn (array $attributes) => [
            'caseref' => $container->caseref,
            'container_id' => $container->id,
        ]);
    }

    /**
     * Dead matter
     */
    public function dead(): static
    {
        return $this->state(fn (array $attributes) => [
            'dead' => true,
        ]);
    }

    /**
     * Matter with responsible person
     */
    public function withResponsible(string $login): static
    {
        return $this->state(fn (array $attributes) => [
            'responsible' => $login,
        ]);
    }

    /**
     * Matter with expiry date
     */
    public function withExpiry(\DateTime $date): static
    {
        return $this->state(fn (array $attributes) => [
            'expire_date' => $date->format('Y-m-d'),
        ]);
    }
}
