<?php

namespace Database\Factories;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'patronymic' => fake()->randomElement([fake()->lastName().'ич', fake()->lastName().'овна', null]),
            'login' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),

            'role_id' => fake()->randomElement([UserRole::Agent, UserRole::Admin]),

            'phone_number' => fake()->unique()->phoneNumber(),
            'phone_verified_at' => fake()->randomElement([now(), null]),

            'timezone' => fake()->timezone(),
            'signature' => fake()->optional()->sentence(),
            'avatar' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
