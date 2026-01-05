<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Factory for creating User instances.
 *
 * Note: The User model uses the 'actor' table directly since the 'users'
 * table is a VIEW on 'actor' (views are not directly writable in PostgreSQL).
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'login' => 'user_'.Str::random(11),  // 16 chars max, unique
            'email' => $this->faker->unique()->safeEmail,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'remember_token' => Str::random(10),
            'default_role' => 'DBRW',
            'company_id' => null,
            'parent_id' => null,
            'language' => 'en',
            // Actor-specific fields required for the actor table
            'phy_person' => true,
            'country' => 'US',
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
     * Unverified user (no-op since email_verified_at doesn't exist in this schema)
     */
    public function unverified(): static
    {
        // email_verified_at doesn't exist in the actor/users schema
        // This method is kept for compatibility but has no effect
        return $this;
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
