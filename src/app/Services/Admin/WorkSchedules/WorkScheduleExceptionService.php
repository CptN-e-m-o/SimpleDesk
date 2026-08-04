<?php

namespace App\Services\Admin\WorkSchedules;

use App\Enums\Admin\WorkScheduleExceptionType;
use App\Models\Admin\WorkScheduleAssignment;
use App\Models\Admin\WorkScheduleException;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkScheduleExceptionService
{
    public function __construct(private readonly WorkScheduleConflictChecker $conflicts) {}

    public function create(WorkScheduleAssignment $assignment, array $data, ?int $actorId = null): WorkScheduleException
    {
        return DB::transaction(function () use ($assignment, $data, $actorId) {
            $this->validate($assignment, $data);
            $exception = $assignment->exceptions()->create(['date' => $data['date'], 'type' => $data['type'], 'reason' => $data['reason'] ?? null, 'created_by' => $actorId, 'updated_by' => $actorId]);
            $this->sync($exception, $data['intervals'] ?? []);

            return $exception->load('intervals');
        });
    }

    public function update(WorkScheduleException $exception, array $data, ?int $actorId = null): WorkScheduleException
    {
        return DB::transaction(function () use ($exception, $data, $actorId) {
            $this->validate($exception->assignment, $data, $exception->id);
            $exception->update(['date' => $data['date'], 'type' => $data['type'], 'reason' => $data['reason'] ?? null, 'updated_by' => $actorId]);
            $this->sync($exception, $data['intervals'] ?? []);

            return $exception->load('intervals');
        });
    }

    private function validate(WorkScheduleAssignment $assignment, array $data, ?int $ignore = null): void
    {
        $date = $data['date'];
        if ($date < $assignment->effective_from->toDateString() || ($assignment->effective_until && $date > $assignment->effective_until->toDateString())) {
            throw ValidationException::withMessages(['date' => 'The exception date must fall within the assignment period.']);
        }
        if ($assignment->exceptions()->whereDate('date', $date)->when($ignore, fn ($q) => $q->whereKeyNot($ignore))->exists()) {
            throw ValidationException::withMessages(['date' => 'An exception already exists for this date.']);
        }
        $type = WorkScheduleExceptionType::from($data['type']);
        $intervals = $data['intervals'] ?? [];
        if ($type === WorkScheduleExceptionType::DayOff && $intervals !== []) {
            throw ValidationException::withMessages(['intervals' => 'Day off cannot contain intervals.']);
        }
        if ($type !== WorkScheduleExceptionType::DayOff && $intervals === []) {
            throw ValidationException::withMessages(['intervals' => 'This exception type requires at least one interval.']);
        }
        $this->conflicts->validateDailyIntervals($intervals);
        if ($type === WorkScheduleExceptionType::ExtraShift) {
            $assignment->loadMissing('schedule.intervals');
            $weekday = (int) CarbonImmutable::parse($date, $assignment->schedule->timezone)->isoWeekday();
            $base = $assignment->schedule->intervals->map(fn ($i) => ['day_of_week' => $i->day_of_week->value, 'starts_at' => $i->starts_at, 'ends_at' => $i->ends_at, 'ends_next_day' => $i->ends_next_day])->all();
            $extras = array_map(fn (array $interval): array => ['day_of_week' => $weekday, ...$interval], $intervals);
            try {
                $this->conflicts->validateWeeklyIntervals([...$base, ...$extras]);
            } catch (ValidationException) {
                throw ValidationException::withMessages(['intervals' => 'Extra shift intervals must not overlap the base schedule, including overnight intervals.']);
            }
        }
    }

    private function sync(WorkScheduleException $exception, array $intervals): void
    {
        $exception->intervals()->delete();
        foreach (array_values($intervals) as $order => $interval) {
            $exception->intervals()->create([...$interval, 'sort_order' => $order]);
        }
    }
}
