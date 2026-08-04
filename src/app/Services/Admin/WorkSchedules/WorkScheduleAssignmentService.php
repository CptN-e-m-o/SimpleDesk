<?php

namespace App\Services\Admin\WorkSchedules;

use App\Models\Admin\WorkSchedule;
use App\Models\Admin\WorkScheduleAssignment;
use App\Models\User\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkScheduleAssignmentService
{
    public function __construct(private readonly WorkScheduleConflictChecker $conflicts) {}

    public function assign(WorkSchedule $schedule, int $userId, string $from, ?string $until, ?int $actorId = null): WorkScheduleAssignment
    {
        return DB::transaction(function () use ($schedule, $userId, $from, $until, $actorId) {
            $schedule->refresh();
            if ($schedule->trashed() || ! $schedule->is_active) {
                throw ValidationException::withMessages(['work_schedule_id' => 'Only active, non-archived schedules can be assigned.']);
            }
            $agent = User::query()->whereKey($userId)->whereHas('roles', fn ($q) => $q->where('type', 'agent'))->first();
            if (! $agent) {
                throw ValidationException::withMessages(['user_id' => 'The selected user must be an agent.']);
            }
            $start = CarbonImmutable::parse($from);
            $end = $until ? CarbonImmutable::parse($until) : null;
            if ($end?->lt($start)) {
                throw ValidationException::withMessages(['effective_until' => 'The end date cannot be before the start date.']);
            }
            if ($this->conflicts->assignmentsOverlap($userId, $start, $end)) {
                throw ValidationException::withMessages(['effective_from' => 'This agent already has an overlapping assignment.']);
            }

            return WorkScheduleAssignment::create(['work_schedule_id' => $schedule->id, 'user_id' => $userId, 'effective_from' => $start, 'effective_until' => $end, 'created_by' => $actorId, 'updated_by' => $actorId]);
        });
    }

    public function bulkAssign(WorkSchedule $schedule, array $userIds, string $from, ?string $until, ?int $actorId = null): array
    {
        return DB::transaction(fn () => collect($userIds)->unique()->map(fn ($id) => $this->assign($schedule, (int) $id, $from, $until, $actorId))->all());
    }

    public function end(WorkScheduleAssignment $assignment, string $until, ?int $actorId = null): void
    {
        $date = CarbonImmutable::parse($until);
        if ($date->lt($assignment->effective_from)) {
            throw ValidationException::withMessages(['effective_until' => 'Invalid assignment end date.']);
        }
        $assignment->update(['effective_until' => $date, 'updated_by' => $actorId]);
    }

    public function deleteFuture(WorkScheduleAssignment $assignment): void
    {
        if (! $assignment->effective_from->isAfter(now($assignment->schedule->timezone)->startOfDay())) {
            throw ValidationException::withMessages(['assignment' => 'Only future assignments can be deleted.']);
        }
        $assignment->delete();
    }
}
