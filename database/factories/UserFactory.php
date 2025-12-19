<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'login' => $this->faker->unique()->userName,
            'email' => $this->faker->unique()->safeEmail,
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'default_role' => 'DBRW',
            'company_id' => null,
            'parent_id' => null,
            'language' => 'en',
        ];
    }

    /**
     * User with admin role
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_role' => 'DBA',
        ]);
    }

    /**
     * User with read-write role
     */
    public function readWrite(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_role' => 'DBRW',
        ]);
    }

    /**
     * User with read-only role
     */
    public function readOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_role' => 'DBRO',
        ]);
    }

    /**
     * User with client role
     */
    public function client(): static
    {
        return $this->state(fn (array $attributes) => [
            'default_role' => 'CLI',
        ]);
    }

    /**
     * Unverified user
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * User with specific language
     */
    public function withLanguage(string $language): static
    {
        return $this->state(fn (array $attributes) => [
            'language' => $language,
        ]);
    }
}
