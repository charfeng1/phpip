<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper($this->faker->unique()->lexify('???')),
            'name' => json_encode([
                'en' => $this->faker->jobTitle,
                'fr' => $this->faker->jobTitle,
            ]),
            'display_order' => $this->faker->numberBetween(1, 100),
            'shareable' => $this->faker->boolean,
            'show_ref' => $this->faker->boolean,
            'show_company' => $this->faker->boolean,
            'show_rate' => $this->faker->boolean,
            'show_date' => $this->faker->boolean,
        ];
    }

    /**
     * Client role
     */
    public function client(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'CLI',
            'name' => json_encode(['en' => 'Client', 'fr' => 'Client']),
            'shareable' => true,
        ]);
    }

    /**
     * Agent role
     */
    public function agent(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'AGT',
            'name' => json_encode(['en' => 'Agent', 'fr' => 'Mandataire']),
            'shareable' => true,
        ]);
    }

    /**
     * Inventor role
     */
    public function inventor(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'INV',
            'name' => json_encode(['en' => 'Inventor', 'fr' => 'Inventeur']),
            'shareable' => true,
        ]);
    }

    /**
     * Applicant role
     */
    public function applicant(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'APP',
            'name' => json_encode(['en' => 'Applicant', 'fr' => 'Demandeur']),
            'shareable' => true,
        ]);
    }

    /**
     * Database Admin role
     */
    public function dba(): static
    {
        return $this->state(fn (array $attributes) => [
            'code' => 'DBA',
            'name' => json_encode(['en' => 'Database Administrator', 'fr' => 'Administrateur']),
        ]);
    }
}
