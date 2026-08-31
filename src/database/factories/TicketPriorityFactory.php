<?php

namespace Database\Factories;

use App\Models\TicketPriority;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class TicketPriorityFactory extends Factory
{
    protected $model = TicketPriority::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['name' => ucfirst($name), 'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1000, 999999), 'description' => fake()->sentence(), 'color' => fake()->hexColor(), 'visibility' => 'public', 'sort_order' => 100, 'is_default' => false, 'is_active' => true, 'is_system' => false];
    }
}
