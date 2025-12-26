<?php

namespace Database\Factories;

use App\Models\Actor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ActorFactory extends Factory
{
    protected $model = Actor::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->company,
            'display_name' => null,
            'first_name' => null,
            'login' => null,
            'password' => null,
            'default_role' => null,
            'function' => null,
            'parent_id' => null,
            'company_id' => null,
            'site_id' => null,
            'phy_person' => false,
            'nationality' => null,
            'small_entity' => false,
            'address' => $this->faker->address,
            'country' => 'US',
            'address_mailing' => null,
            'country_mailing' => null,
            'address_billing' => null,
            'country_billing' => null,
            'email' => $this->faker->safeEmail,
            'phone' => $this->faker->phoneNumber,
            'fax' => null,
            'url' => $this->faker->url,
            'VAT_number' => null,
            'ren_discount' => 0,
            'warn' => false,
            'notes' => null,
            'legal_form' => null,
            'registration_no' => null,
            'language' => 'en',
        ];
    }

    /**
     * Individual person actor
     */
    public function person(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->lastName,
            'first_name' => $this->faker->firstName,
            'phy_person' => true,
        ]);
    }

    /**
     * Company actor
     */
    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => $this->faker->company,
            'phy_person' => false,
            'legal_form' => $this->faker->randomElement(['Inc.', 'Ltd.', 'LLC', 'GmbH', 'SA']),
        ]);
    }

    /**
     * Actor with client role
     */
    public function asClient(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_role' => 'CLI',
        ]);
    }

    /**
     * Actor with agent role
     */
    public function asAgent(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_role' => 'AGT',
        ]);
    }

    /**
     * Actor with DBA (admin) role
     */
    public function asAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_role' => 'DBA',
            'login' => substr($this->faker->unique()->userName, 0, 16),
        ]);
    }

    /**
     * Actor that can login (user)
     */
    public function withLogin(): static
    {
        return $this->state(fn (array $attributes) => [
            'login' => substr($this->faker->unique()->userName, 0, 16),
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * Small entity actor
     */
    public function smallEntity(): static
    {
        return $this->state(fn (array $attributes) => [
            'small_entity' => true,
        ]);
    }

    /**
     * Actor with warning flag
     */
    public function withWarning(): static
    {
        return $this->state(fn (array $attributes) => [
            'warn' => true,
            'notes' => $this->faker->sentence,
        ]);
    }
}
