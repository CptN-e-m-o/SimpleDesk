<?php

namespace Database\Factories\User;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'email' => fake()
                ->unique()
                ->safeEmail(),

            'username' => fake()
                ->unique()
                ->userName(),

            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),

            'location' => null,

            'phone_country_iso2' => null,
            'phone_country_code' => null,
            'phone_number' => null,
            'phone_ext' => null,

            'mobile_country_iso2' => null,
            'mobile_country_code' => null,
            'mobile_number' => null,

            'timezone' => 'Europe/Berlin',
            'signature' => null,

            'email_verified_at' => now(),

            'password' => static::$password ??= Hash::make(
                'password'
            ),

            'remember_token' => Str::random(10),
            'is_active' => true,
        ];
    }

    public function unverified(): static
    {
        return $this->state(
            fn (array $attributes): array => [
                'email_verified_at' => null,
            ]
        );
    }

    public function inactive(): static
    {
        return $this->state(
            fn (array $attributes): array => [
                'is_active' => false,
            ]
        );
    }
}
