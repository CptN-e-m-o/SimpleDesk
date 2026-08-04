<?php

namespace Database\Factories\Admin;

use App\Models\Admin\WorkSchedule;
use App\Models\Admin\WorkScheduleAssignment;
use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkScheduleAssignmentFactory extends Factory
{
    protected $model = WorkScheduleAssignment::class;

    public function definition(): array
    {
        return ['work_schedule_id' => WorkSchedule::factory(), 'user_id' => User::factory(), 'effective_from' => now()->startOfMonth(), 'effective_until' => null];
    }

    public function future(): static
    {
        return $this->state(['effective_from' => now()->addMonth()->startOfMonth()]);
    }

    public function completed(): static
    {
        return $this->state(['effective_from' => now()->subMonths(2), 'effective_until' => now()->subMonth()]);
    }
}
