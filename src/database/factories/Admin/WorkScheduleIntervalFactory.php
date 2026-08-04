<?php

namespace Database\Factories\Admin;

use App\Models\Admin\WorkSchedule;
use App\Models\Admin\WorkScheduleInterval;
use Illuminate\Database\Eloquent\Factories\Factory;

class WorkScheduleIntervalFactory extends Factory
{
    protected $model = WorkScheduleInterval::class;

    public function definition(): array
    {
        return ['work_schedule_id' => WorkSchedule::factory(), 'day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '17:00', 'ends_next_day' => false, 'sort_order' => 0];
    }

    public function night(): static
    {
        return $this->state(['starts_at' => '22:00', 'ends_at' => '06:00', 'ends_next_day' => true]);
    }
}
