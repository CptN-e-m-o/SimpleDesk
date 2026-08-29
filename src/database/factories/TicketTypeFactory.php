<?php

namespace Database\Factories;

use App\Models\TicketType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TicketTypeFactory extends Factory
{
    protected $model = TicketType::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['name' => ucfirst($name), 'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 999999), 'description' => fake()->sentence(), 'visibility' => 'public', 'sort_order' => 100, 'is_active' => true, 'is_system' => false];
    }
}
