<?php

namespace Tests\Feature\Admin\WorkSchedules;

use App\Models\Admin\WorkSchedule;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class WorkScheduleAdminTest extends TestCase
{
    use DatabaseMigrations;

    public function test_index_requires_view_permission(): void
    {
        $this->actingAs(User::factory()->create())->get(route('admin.work-schedules.index'))->assertForbidden();
        $this->actingAs($this->user(['admin.staff.work_schedules.view']))->get(route('admin.work-schedules.index'))->assertOk()->assertInertia(fn ($page) => $page->component('Admin/WorkSchedules/Index'));
    }

    public function test_schedule_can_be_created_updated_duplicated_archived_and_restored(): void
    {
        $admin = $this->user(['admin.staff.work_schedules.view', 'admin.staff.work_schedules.create', 'admin.staff.work_schedules.update', 'admin.staff.work_schedules.archive']);
        $this->actingAs($admin)->post(route('admin.work-schedules.store'), $this->payload())->assertRedirect();
        $schedule = WorkSchedule::query()->sole();
        $this->assertCount(2, $schedule->intervals);
        $this->actingAs($admin)->put(route('admin.work-schedules.update', $schedule), [...$this->payload(), 'name' => 'Updated Support'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.work-schedules.duplicate', $schedule))->assertRedirect();
        $this->assertDatabaseCount('work_schedules', 2);
        $this->actingAs($admin)->delete(route('admin.work-schedules.destroy', $schedule))->assertRedirect();
        $this->assertSoftDeleted($schedule);
        $this->actingAs($admin)->post(route('admin.work-schedules.restore', $schedule->id))->assertRedirect();
        $this->assertFalse($schedule->fresh()->is_active);
    }

    public function test_invalid_intervals_are_transactional(): void
    {
        $admin = $this->user(['admin.staff.work_schedules.create']);
        $payload = $this->payload();
        $payload['intervals'][1] = ['day_of_week' => 1, 'starts_at' => '12:00', 'ends_at' => '15:00', 'ends_next_day' => false];
        $this->actingAs($admin)->post(route('admin.work-schedules.store'), $payload)->assertSessionHasErrors('intervals');
        $this->assertDatabaseEmpty('work_schedules');
        $this->assertDatabaseEmpty('work_schedule_intervals');
    }

    public function test_assignment_requires_agent_and_active_schedule(): void
    {
        $admin = $this->user(['admin.staff.work_schedules.manage_assignments']);
        $schedule = WorkSchedule::factory()->create();
        $nonAgent = User::factory()->create();
        $this->actingAs($admin)->post(route('admin.work-schedules.assignments.store', $schedule), ['user_ids' => [$nonAgent->id], 'effective_from' => '2026-09-01'])->assertSessionHasErrors('user_id');
        $schedule->update(['is_active' => false]);
        $agent = $this->agent();
        $this->actingAs($admin)->post(route('admin.work-schedules.assignments.store', $schedule), ['user_ids' => [$agent->id], 'effective_from' => '2026-09-01'])->assertSessionHasErrors('work_schedule_id');
    }

    private function payload(): array
    {
        return ['name' => 'Standard Support', 'description' => null, 'timezone' => 'Europe/Berlin', 'is_active' => true, 'intervals' => [['day_of_week' => 1, 'starts_at' => '09:00', 'ends_at' => '13:00', 'ends_next_day' => false], ['day_of_week' => 1, 'starts_at' => '14:00', 'ends_at' => '18:00', 'ends_next_day' => false]], 'agent_ids' => []];
    }

    private function agent(): User
    {
        $user = User::factory()->create();
        $role = Role::create(['name' => 'agent-'.$user->id, 'label' => 'Agent', 'type' => 'agent']);
        $user->roles()->attach($role);

        return $user;
    }

    private function user(array $keys): User
    {
        $user = User::factory()->create();
        $group = PermissionGroup::create(['key' => 'ws-'.$user->id, 'label' => 'WS', 'panel' => 'admin', 'type' => 'agent', 'sort_order' => 1]);
        $role = Role::create(['name' => 'ws-'.$user->id, 'label' => 'WS', 'type' => 'agent']);
        $ids = collect($keys)->map(fn ($key) => Permission::create(['permission_group_id' => $group->id, 'key' => $key, 'label' => $key, 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 1])->id);
        $role->permissions()->sync($ids);
        $user->roles()->attach($role);

        return $user;
    }
}
