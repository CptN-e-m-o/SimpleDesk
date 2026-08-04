<?php

namespace Tests\Feature\Admin\WorkSchedules;

use App\Enums\Admin\WorkScheduleExceptionType;
use App\Models\Admin\WorkScheduleAssignment;
use App\Services\Admin\WorkSchedules\WorkScheduleExceptionService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkScheduleExceptionTest extends TestCase
{
    use DatabaseMigrations;

    public function test_day_off_and_custom_hours_rules_are_enforced_transactionally(): void
    {
        $assignment = WorkScheduleAssignment::factory()->create(['effective_from' => '2026-01-01', 'effective_until' => '2026-12-31']);
        $service = app(WorkScheduleExceptionService::class);
        $service->create($assignment, ['date' => '2026-08-10', 'type' => WorkScheduleExceptionType::DayOff->value, 'reason' => 'Holiday', 'intervals' => []]);
        $this->assertTrue($assignment->exceptions()->whereDate('date', '2026-08-10')->where('type', 'day_off')->exists());
        try {
            $service->create($assignment, ['date' => '2026-08-11', 'type' => 'custom_hours', 'intervals' => []]);
            $this->fail('Validation exception expected.');
        } catch (ValidationException) {
        }
        $this->assertDatabaseMissing('work_schedule_exceptions', ['date' => '2026-08-11']);
    }

    public function test_exception_must_be_inside_assignment_period(): void
    {
        $assignment = WorkScheduleAssignment::factory()->create(['effective_from' => '2026-08-01', 'effective_until' => '2026-08-31']);
        $this->expectException(ValidationException::class);
        app(WorkScheduleExceptionService::class)->create($assignment, ['date' => '2026-09-01', 'type' => 'day_off', 'intervals' => []]);
    }
}
