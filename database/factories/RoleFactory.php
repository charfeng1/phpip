<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    /**
     * Counter for generating unique test role codes.
     */
    protected static int $testCodeCounter = 0;

    public function definition(): array
    {
        // Use TST prefix to avoid collision with real role codes
        $counter = static::$testCodeCounter++;
        $code = sprintf('T%02d', $counter);

        return [
            'code' => $code,
            'name' => json_encode([
                'en' => 'Test Role '.$counter,
                'fr' => 'Rôle Test '.$counter,
            ]),
            'display_order' => $this->faker->numberBetween(100, 200),
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
