<?php

namespace App\Services\Admin\WorkSchedules;

use App\Enums\Admin\WorkScheduleExceptionType;
use App\Models\Admin\WorkScheduleAssignment;
use App\Models\User\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class AgentScheduleResolver
{
    public function resolveAssignment(User $agent, CarbonInterface $at): ?WorkScheduleAssignment
    {
        return WorkScheduleAssignment::query()->where('user_id', $agent->id)
            ->whereHas('schedule', fn ($q) => $q->where('is_active', true))
            ->with(['schedule.intervals', 'exceptions.intervals'])->orderByDesc('effective_from')->get()
            ->first(function ($assignment) use ($at) {
                $date = CarbonImmutable::instance($at)->setTimezone($assignment->schedule->timezone)->toDateString();

                return $assignment->effective_from->toDateString() <= $date && ($assignment->effective_until === null || $assignment->effective_until->toDateString() >= $date);
            });
    }

    public function intervalsForDate(User $agent, CarbonInterface $date): Collection
    {
        $assignment = $this->resolveAssignment($agent, $date);
        if (! $assignment) {
            return collect();
        }
        $local = CarbonImmutable::instance($date)->setTimezone($assignment->schedule->timezone);
        $exception = $assignment->exceptions->first(fn ($e) => $e->date->toDateString() === $local->toDateString());
        if ($exception?->type === WorkScheduleExceptionType::DayOff) {
            return collect();
        }
        $base = $assignment->schedule->intervals->filter(fn ($i) => $i->day_of_week->value === $local->isoWeekday());
        $source = $exception?->type === WorkScheduleExceptionType::CustomHours ? $exception->intervals : $base;
        if ($exception?->type === WorkScheduleExceptionType::ExtraShift) {
            $source = $base->concat($exception->intervals);
        }

        return $source->map(fn ($i) => $this->materialize($local, $i->starts_at, $i->ends_at, $i->ends_next_day))->sortBy('start')->values();
    }

    public function isWorking(User $agent, CarbonInterface $at): bool
    {
        $moment = CarbonImmutable::instance($at);
        foreach ([$moment, $moment->subDay()] as $date) {
            foreach ($this->intervalsForDate($agent, $date) as $range) {
                if ($moment->greaterThanOrEqualTo($range['start']) && $moment->lessThan($range['end'])) {
                    return true;
                }
            }
        }

        return false;
    }

    public function nextWorkingInterval(User $agent, CarbonInterface $after): ?array
    {
        $moment = CarbonImmutable::instance($after);
        for ($day = -1; $day <= 370; $day++) {
            foreach ($this->intervalsForDate($agent, $moment->addDays($day)) as $range) {
                if ($range['end']->greaterThan($moment)) {
                    return $range;
                }
            }
        }

        return null;
    }

    private function materialize(CarbonImmutable $date, string $start, string $end, bool $next): array
    {
        $startAt = CarbonImmutable::parse($date->toDateString().' '.$start, $date->timezone);
        $endAt = CarbonImmutable::parse($date->toDateString().' '.$end, $date->timezone)->addDays($next ? 1 : 0);

        return ['start' => $startAt, 'end' => $endAt];
    }
}
