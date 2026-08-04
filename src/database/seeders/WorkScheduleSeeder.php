<?php

namespace Database\Seeders;

use App\Enums\Admin\WorkScheduleExceptionType;
use App\Models\Admin\WorkSchedule;
use App\Models\User\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkScheduleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $definitions = [
                'Standard Support' => collect(range(1, 5))->flatMap(fn (int $day) => [['day_of_week' => $day, 'starts_at' => '09:00', 'ends_at' => '13:00', 'ends_next_day' => false], ['day_of_week' => $day, 'starts_at' => '14:00', 'ends_at' => '18:00', 'ends_next_day' => false]])->all(),
                'Night Support' => collect(range(1, 5))->map(fn (int $day) => ['day_of_week' => $day, 'starts_at' => '22:00', 'ends_at' => '06:00', 'ends_next_day' => true])->all(),
                'Weekend Support' => collect([6, 7])->map(fn (int $day) => ['day_of_week' => $day, 'starts_at' => '10:00', 'ends_at' => '18:00', 'ends_next_day' => false])->all(),
            ];
            $schedules = [];
            foreach ($definitions as $name => $intervals) {
                $schedule = WorkSchedule::withTrashed()->firstOrNew(['name' => $name]);
                if ($schedule->trashed()) {
                    $schedule->restore();
                }
                $schedule->fill(['description' => 'Demonstration work schedule.', 'timezone' => 'Europe/Berlin', 'is_active' => true])->save();
                $schedule->intervals()->delete();
                foreach ($intervals as $order => $interval) {
                    $schedule->intervals()->create([...$interval, 'sort_order' => $order]);
                }
                $schedules[] = $schedule;
            }
            $agents = User::query()->whereHas('roles', fn ($q) => $q->where('type', 'agent'))->orderBy('id')->limit(3)->get();
            foreach ($agents as $index => $agent) {
                $assignment = $schedules[$index]->assignments()->updateOrCreate(['user_id' => $agent->id, 'effective_from' => '2026-01-01'], ['effective_until' => null]);
                $type = WorkScheduleExceptionType::cases()[$index];
                $exception = $assignment->exceptions()->updateOrCreate(['date' => '2026-12-24'], ['type' => $type, 'reason' => 'Demonstration exception']);
                $exception->intervals()->delete();
                if ($type !== WorkScheduleExceptionType::DayOff) {
                    $exception->intervals()->create(['starts_at' => $type === WorkScheduleExceptionType::CustomHours ? '10:00' : '18:00', 'ends_at' => $type === WorkScheduleExceptionType::CustomHours ? '14:00' : '20:00', 'ends_next_day' => false, 'sort_order' => 0]);
                }
            }
        });
    }
}
