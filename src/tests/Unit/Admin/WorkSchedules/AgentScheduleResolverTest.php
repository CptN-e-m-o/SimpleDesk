<?php

namespace Tests\Unit\Admin\WorkSchedules;

use App\Enums\Admin\WorkScheduleExceptionType;
use App\Models\Admin\WorkSchedule;
use App\Models\Admin\WorkScheduleAssignment;
use App\Models\Role;
use App\Models\User\User;
use App\Services\Admin\WorkSchedules\AgentScheduleResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgentScheduleResolverTest extends TestCase
{
    use RefreshDatabase;

    private User $agent;

    private WorkSchedule $schedule;

    private WorkScheduleAssignment $assignment;

    protected function setUp(): void
    {
        parent::setUp();
        $role = Role::create(['name' => 'agent', 'label' => 'Agent', 'type' => 'agent']);
        $this->agent = User::factory()->create();
        $this->agent->roles()->attach($role);
        $this->schedule = WorkSchedule::factory()->create(['timezone' => 'Europe/Berlin']);
        $this->schedule->intervals()->delete();
        $this->schedule->intervals()->create(['day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '13:00', 'ends_next_day' => false, 'sort_order' => 0]);
        $this->schedule->intervals()->create(['day_of_week' => 1, 'starts_at' => '14:00', 'ends_at' => '18:00', 'ends_next_day' => false, 'sort_order' => 1]);
        $this->assignment = WorkScheduleAssignment::factory()->create(['work_schedule_id' => $this->schedule, 'user_id' => $this->agent, 'effective_from' => '2026-01-01']);
    }

    public function test_working_break_and_next_interval(): void
    {
        $r = app(AgentScheduleResolver::class);
        $this->assertTrue($r->isWorking($this->agent, CarbonImmutable::parse('2026-08-03 10:00', 'Europe/Berlin')));
        $this->assertFalse($r->isWorking($this->agent, CarbonImmutable::parse('2026-08-03 13:30', 'Europe/Berlin')));
        $this->assertSame('14:00', $r->nextWorkingInterval($this->agent, CarbonImmutable::parse('2026-08-03 13:30', 'Europe/Berlin'))['start']->format('H:i'));
    }

    public function test_day_off_replaces_base_hours(): void
    {
        $this->assignment->exceptions()->create(['date' => '2026-08-03', 'type' => WorkScheduleExceptionType::DayOff]);
        $this->assertFalse(app(AgentScheduleResolver::class)->isWorking($this->agent, CarbonImmutable::parse('2026-08-03 10:00', 'Europe/Berlin')));
    }

    public function test_inactive_and_missing_assignments_do_not_resolve(): void
    {
        $this->schedule->update(['is_active' => false]);
        $this->assertNull(app(AgentScheduleResolver::class)->resolveAssignment($this->agent, CarbonImmutable::parse('2026-08-03')));
        $this->assertNull(app(AgentScheduleResolver::class)->resolveAssignment(User::factory()->create(), CarbonImmutable::parse('2026-08-03')));
    }

    public function test_overnight_interval_is_found_from_previous_local_date(): void
    {
        $this->schedule->intervals()->delete();
        $this->schedule->intervals()->create(['day_of_week' => 7, 'starts_at' => '22:00', 'ends_at' => '06:00', 'ends_next_day' => true, 'sort_order' => 0]);
        $this->assertTrue(app(AgentScheduleResolver::class)->isWorking($this->agent, CarbonImmutable::parse('2026-08-03 02:00', 'Europe/Berlin')));
    }

    public function test_timezone_conversion_works_across_berlin_dst_transition(): void
    {
        $this->schedule->intervals()->delete();
        $this->schedule->intervals()->create(['day_of_week' => 7, 'starts_at' => '01:00', 'ends_at' => '04:00', 'ends_next_day' => false, 'sort_order' => 0]);
        $resolver = app(AgentScheduleResolver::class);
        $this->assertTrue($resolver->isWorking($this->agent, CarbonImmutable::parse('2026-03-29 00:30', 'UTC')));
        $this->assertFalse($resolver->isWorking($this->agent, CarbonImmutable::parse('2026-03-29 03:00', 'UTC')));
    }
}
