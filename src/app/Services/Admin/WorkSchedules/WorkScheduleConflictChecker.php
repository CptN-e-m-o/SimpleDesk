<?php

namespace App\Services\Admin\WorkSchedules;

use App\Models\Admin\WorkScheduleAssignment;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class WorkScheduleConflictChecker
{
    public function validateWeeklyIntervals(array $intervals): void
    {
        if ($intervals === []) {
            throw ValidationException::withMessages(['intervals' => 'A work schedule must contain at least one interval.']);
        }

        $ranges = [];
        foreach ($intervals as $index => $interval) {
            $day = (int) ($interval['day_of_week'] ?? 0);
            if ($day < 1 || $day > 7) {
                throw ValidationException::withMessages(["intervals.$index.day_of_week" => 'Select a valid weekday.']);
            }
            $range = $this->range($interval, ($day - 1) * 1440, "intervals.$index");
            $ranges[] = [...$range, 'index' => $index];
        }

        $this->assertNoOverlaps($ranges, 'intervals');
    }

    public function validateDailyIntervals(array $intervals, string $key = 'intervals'): void
    {
        $ranges = [];
        foreach ($intervals as $index => $interval) {
            $ranges[] = [...$this->range($interval, 0, "$key.$index"), 'index' => $index];
        }
        $this->assertNoOverlaps($ranges, $key);
    }

    public function assignmentsOverlap(int $userId, CarbonInterface $from, ?CarbonInterface $until, ?int $ignoreId = null): bool
    {
        return WorkScheduleAssignment::query()
            ->where('user_id', $userId)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->whereDate('effective_from', '<=', ($until ?? now()->addYears(100))->toDateString())
            ->where(fn ($query) => $query->whereNull('effective_until')->orWhereDate('effective_until', '>=', $from->toDateString()))
            ->lockForUpdate()
            ->exists();
    }

    public function overlapsBase(array $base, array $extras): bool
    {
        $ranges = [];
        foreach ([...$base, ...$extras] as $index => $interval) {
            $ranges[] = [...$this->range($interval, 0, "intervals.$index"), 'index' => $index];
        }
        usort($ranges, fn ($a, $b) => $a['start'] <=> $b['start']);
        for ($i = 1; $i < count($ranges); $i++) {
            if ($ranges[$i]['start'] < $ranges[$i - 1]['end']) {
                return true;
            }
        }

        return false;
    }

    private function range(array $interval, int $offset, string $key): array
    {
        $start = $this->minutes((string) ($interval['starts_at'] ?? ''));
        $end = $this->minutes((string) ($interval['ends_at'] ?? ''));
        $next = (bool) ($interval['ends_next_day'] ?? false);
        if ($start === null || $end === null) {
            throw ValidationException::withMessages([$key => 'Use times in HH:MM format.']);
        }
        if ($start === $end) {
            throw ValidationException::withMessages([$key => 'Start and end time must differ.']);
        }
        if (! $next && $end <= $start) {
            throw ValidationException::withMessages([$key => 'End time must be later, or Next day must be enabled.']);
        }
        if ($next && $end >= $start) {
            throw ValidationException::withMessages([$key => 'Next-day intervals must end earlier than their start time.']);
        }

        return ['start' => $offset + $start, 'end' => $offset + $end + ($next ? 1440 : 0)];
    }

    private function assertNoOverlaps(array $ranges, string $key): void
    {
        usort($ranges, fn ($a, $b) => $a['start'] <=> $b['start']);
        $expanded = [...$ranges];
        foreach ($ranges as $range) {
            $expanded[] = ['start' => $range['start'] + 10080, 'end' => $range['end'] + 10080, 'index' => $range['index']];
        }
        usort($expanded, fn ($a, $b) => $a['start'] <=> $b['start']);
        for ($i = 1; $i < count($expanded); $i++) {
            if ($expanded[$i]['start'] < $expanded[$i - 1]['end']) {
                throw ValidationException::withMessages([$key => 'Work intervals must not overlap, including across adjacent days.']);
            }
        }
    }

    private function minutes(string $time): ?int
    {
        if (preg_match('/^(\d{2}):(\d{2})(?::\d{2})?$/', $time, $m) !== 1) {
            return null;
        }
        $h = (int) $m[1];
        $min = (int) $m[2];

        return $h < 24 && $min < 60 ? $h * 60 + $min : null;
    }
}
