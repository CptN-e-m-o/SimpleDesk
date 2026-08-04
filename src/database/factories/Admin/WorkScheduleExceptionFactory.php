<?php

namespace Database\Factories\Admin;

use App\Enums\Admin\WorkScheduleExceptionType;
use App\Models\Admin\WorkScheduleAssignment;
use App\Models\Admin\WorkScheduleException;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkScheduleExceptionFactory extends Factory
{
    protected $model = WorkScheduleException::class;

    public function definition(): array
    {
        return ['work_schedule_assignment_id' => WorkScheduleAssignment::factory(), 'date' => now()->toDateString(), 'type' => WorkScheduleExceptionType::DayOff, 'reason' => fake()->sentence()];
    }

    public function customHours(): static
    {
        return $this->state(['type' => WorkScheduleExceptionType::CustomHours]);
    }

    public function extraShift(): static
    {
        return $this->state(['type' => WorkScheduleExceptionType::ExtraShift]);
    }
}
