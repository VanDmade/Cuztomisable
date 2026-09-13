<?php

namespace VanDmade\Cuztomisable\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use VanDmade\Cuztomisable\Models\Users\User;

/**
 * Default factory for the package's User model.
 */
class UserFactory extends Factory
{

    protected $model = User::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'username' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'timezone' => 'America/New_York',
            'locked' => false,
            'change_password' => false,
            'multi_factor_authentication' => false,
            'admin' => false,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'admin' => true,
        ]);
    }

    public function locked(): static
    {
        return $this->state(fn (array $attributes) => [
            'locked' => true,
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

}
