<?php

namespace App\Services\Admin\WorkSchedules;

use App\Models\Admin\WorkSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkScheduleService
{
    public function __construct(private readonly WorkScheduleConflictChecker $conflicts) {}

    public function create(array $data, ?int $actorId = null): WorkSchedule
    {
        return DB::transaction(function () use ($data, $actorId): WorkSchedule {
            $this->assertUniqueName($data['name']);
            $this->conflicts->validateWeeklyIntervals($data['intervals']);
            $schedule = WorkSchedule::create($this->values($data, $actorId, true));
            $this->syncIntervals($schedule, $data['intervals']);

            return $schedule->load('intervals');
        });
    }

    public function update(WorkSchedule $schedule, array $data, ?int $actorId = null): WorkSchedule
    {
        return DB::transaction(function () use ($schedule, $data, $actorId): WorkSchedule {
            $this->assertUniqueName($data['name'], $schedule->id);
            $this->conflicts->validateWeeklyIntervals($data['intervals']);
            $schedule->update($this->values($data, $actorId, false));
            $this->syncIntervals($schedule, $data['intervals']);

            return $schedule->load('intervals');
        });
    }

    public function duplicate(WorkSchedule $schedule, ?int $actorId = null): WorkSchedule
    {
        $schedule->load('intervals');
        $name = $schedule->name.' Copy';
        $counter = 2;
        while (WorkSchedule::query()->where('name', $name)->exists()) {
            $name = $schedule->name.' Copy '.$counter++;
        }

        return $this->create(['name' => $name, 'description' => $schedule->description, 'timezone' => $schedule->timezone, 'is_active' => false,
            'intervals' => $schedule->intervals->map(fn ($i) => ['day_of_week' => $i->day_of_week->value, 'starts_at' => $i->starts_at, 'ends_at' => $i->ends_at, 'ends_next_day' => $i->ends_next_day])->all()], $actorId);
    }

    public function archive(WorkSchedule $schedule): void
    {
        $today = now($schedule->timezone)->toDateString();
        if ($schedule->assignments()->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', $today))->exists()) {
            throw ValidationException::withMessages(['schedule' => 'A schedule with current or future assignments cannot be archived.']);
        }
        $schedule->update(['is_active' => false]);
        $schedule->delete();
    }

    public function restore(int $id): WorkSchedule
    {
        $schedule = WorkSchedule::onlyTrashed()->findOrFail($id);
        $schedule->restore();
        $schedule->update(['is_active' => false]);

        return $schedule;
    }

    private function values(array $data, ?int $actorId, bool $creating): array
    {
        return ['name' => trim($data['name']), 'description' => isset($data['description']) ? trim($data['description']) ?: null : null,
            'timezone' => $data['timezone'], 'is_active' => (bool) $data['is_active'], 'updated_by' => $actorId] + ($creating ? ['created_by' => $actorId] : []);
    }

    private function syncIntervals(WorkSchedule $schedule, array $intervals): void
    {
        $schedule->intervals()->delete();
        foreach (array_values($intervals) as $order => $interval) {
            $schedule->intervals()->create([...$interval, 'sort_order' => $order]);
        }
    }

    private function assertUniqueName(string $name, ?int $ignore = null): void
    {
        if (WorkSchedule::query()->where('name', trim($name))->when($ignore, fn ($q) => $q->whereKeyNot($ignore))->exists()) {
            throw ValidationException::withMessages(['name' => 'An active work schedule with this name already exists.']);
        }
    }
}
