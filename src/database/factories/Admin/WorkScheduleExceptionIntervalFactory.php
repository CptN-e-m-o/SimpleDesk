<?php

namespace Database\Factories\Admin;

use App\Models\Admin\WorkScheduleException;
use App\Models\Admin\WorkScheduleExceptionInterval;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkScheduleExceptionIntervalFactory extends Factory
{
    protected $model = WorkScheduleExceptionInterval::class;

    public function definition(): array
    {
        return ['work_schedule_exception_id' => WorkScheduleException::factory(), 'starts_at' => '10:00', 'ends_at' => '16:00', 'ends_next_day' => false, 'sort_order' => 0];
    }

    public function night(): static
    {
        return $this->state(['starts_at' => '22:00', 'ends_at' => '06:00', 'ends_next_day' => true]);
    }
}
