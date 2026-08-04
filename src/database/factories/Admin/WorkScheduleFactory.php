<?php

namespace Database\Factories\Admin;

use App\Models\Admin\WorkSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkScheduleFactory extends Factory
{
    protected $model = WorkSchedule::class;

    public function definition(): array
    {
        return ['name' => fake()->unique()->words(3, true), 'description' => fake()->optional()->sentence(), 'timezone' => 'Europe/Berlin', 'is_active' => true];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function configure(): static
    {
        return $this->afterCreating(fn (WorkSchedule $s) => $s->intervals()->exists() ?: $s->intervals()->create(['day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '17:00', 'ends_next_day' => false, 'sort_order' => 0]));
    }
}
