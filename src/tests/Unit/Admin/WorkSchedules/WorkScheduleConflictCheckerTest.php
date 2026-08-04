<?php

namespace Tests\Unit\Admin\WorkSchedules;

use App\Services\Admin\WorkSchedules\WorkScheduleConflictChecker;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WorkScheduleConflictCheckerTest extends TestCase
{
    public function test_non_overlapping_and_touching_intervals_are_allowed(): void
    {
        app(WorkScheduleConflictChecker::class)->validateWeeklyIntervals([
            ['day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '13:00', 'ends_next_day' => false],
            ['day_of_week' => 1, 'starts_at' => '13:00', 'ends_at' => '18:00', 'ends_next_day' => false],
        ]);
        $this->addToAssertionCount(1);
    }

    #[DataProvider('conflicts')]
    public function test_conflicts_are_rejected(array $intervals): void
    {
        $this->expectException(ValidationException::class);
        app(WorkScheduleConflictChecker::class)->validateWeeklyIntervals($intervals);
    }

    public static function conflicts(): array
    {
        return [
            'same day' => [[['day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '14:00', 'ends_next_day' => false], ['day_of_week' => 1, 'starts_at' => '13:00', 'ends_at' => '18:00', 'ends_next_day' => false]]],
            'overnight next day' => [[['day_of_week' => 1, 'starts_at' => '22:00', 'ends_at' => '06:00', 'ends_next_day' => true], ['day_of_week' => 2, 'starts_at' => '05:00', 'ends_at' => '10:00', 'ends_next_day' => false]]],
            'equal' => [[['day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '09:00', 'ends_next_day' => false]]],
        ];
    }
}
