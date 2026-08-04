<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkSchedules\WorkScheduleRequest;
use App\Models\Admin\WorkSchedule;
use App\Models\User\User;
use App\Services\Admin\WorkSchedules\WorkScheduleAssignmentService;
use App\Services\Admin\WorkSchedules\WorkScheduleService;
use DateTimeZone;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkScheduleController extends Controller
{
    public function __construct(private readonly WorkScheduleService $schedules, private readonly WorkScheduleAssignmentService $assignments) {}

    public function index(Request $request): Response
    {
        $status = $request->string('status', 'all')->toString();
        $query = WorkSchedule::query()->visible($status)->with('intervals')->withCount(['assignments as assigned_agents_count' => fn ($q) => $q->where(fn ($q) => $q->whereNull('effective_until')->orWhereDate('effective_until', '>=', now()->toDateString()))]);
        $query->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->when($request->filled('timezone'), fn ($q) => $q->where('timezone', $request->string('timezone')->toString()))
            ->when($request->filled('agent_id'), fn ($q) => $q->whereHas('assignments', fn ($q) => $q->where('user_id', $request->integer('agent_id'))));
        $sort = in_array($request->string('sort')->toString(), ['name', 'timezone', 'updated_at'], true) ? $request->string('sort')->toString() : 'updated_at';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        return Inertia::render('Admin/WorkSchedules/Index', ['schedules' => $query->orderBy($sort, $direction)->paginate(15)->withQueryString()->through(fn ($s) => $this->scheduleData($s)), 'filters' => $request->only(['search', 'status', 'timezone', 'agent_id', 'sort', 'direction']), 'timezones' => DateTimeZone::listIdentifiers(), 'agents' => $this->agents(), 'permissions' => $this->permissions($request)]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Admin/WorkSchedules/Create', ['timezones' => DateTimeZone::listIdentifiers(), 'defaultTimezone' => config('app.timezone'), 'agents' => $this->agents()]);
    }

    public function store(WorkScheduleRequest $request): RedirectResponse
    {
        \DB::transaction(function () use ($request) {
            $data = $request->validated();

            $schedule = $this->schedules->create(
                $data,
                $request->user()->id
            );

            if (($data['agent_ids'] ?? []) !== []) {
                $this->assignments->bulkAssign(
                    $schedule,
                    $data['agent_ids'],
                    $data['effective_from'],
                    $data['effective_until'] ?? null,
                    $request->user()->id
                );
            }
        });

        return redirect()
            ->route('admin.work-schedules.index')
            ->with(
                'success',
                'Work schedule created successfully.'
            );
    }

    public function show(Request $request, WorkSchedule $workSchedule): Response
    {
        $workSchedule->load(['intervals', 'assignments.agent', 'assignments.exceptions.intervals']);

        return Inertia::render('Admin/WorkSchedules/Show', ['schedule' => $this->scheduleData($workSchedule, true), 'agents' => $this->agents(), 'permissions' => $this->permissions($request)]);
    }

    public function edit(WorkSchedule $workSchedule): Response
    {
        $workSchedule->load('intervals');

        return Inertia::render('Admin/WorkSchedules/Edit', ['schedule' => $this->scheduleData($workSchedule), 'timezones' => DateTimeZone::listIdentifiers()]);
    }

    public function update(WorkScheduleRequest $request, WorkSchedule $workSchedule): RedirectResponse
    {
        $this->schedules->update($workSchedule, $request->validated(), $request->user()->id);

        return redirect()->route('admin.work-schedules.show', $workSchedule)->with('success', 'Work schedule updated successfully.');
    }

    public function duplicate(Request $request, WorkSchedule $workSchedule): RedirectResponse
    {
        $copy = $this->schedules->duplicate($workSchedule, $request->user()->id);

        return redirect()->route('admin.work-schedules.edit', $copy)->with('success', 'Work schedule duplicated.');
    }

    public function toggle(Request $request, WorkSchedule $workSchedule): RedirectResponse
    {
        $workSchedule->update(['is_active' => ! $workSchedule->is_active, 'updated_by' => $request->user()->id]);

        return back()->with('success', 'Work schedule status updated.');
    }

    public function destroy(WorkSchedule $workSchedule): RedirectResponse
    {
        $this->schedules->archive($workSchedule);

        return redirect()->route('admin.work-schedules.index')->with('success', 'Work schedule archived.');
    }

    public function restore(int $workSchedule): RedirectResponse
    {
        $schedule = $this->schedules->restore($workSchedule);

        return redirect()->route('admin.work-schedules.show', $schedule)->with('success', 'Work schedule restored disabled.');
    }

    private function agents(): array
    {
        return User::query()->whereHas('roles', fn ($q) => $q->where('type', 'agent'))->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email'])->map(fn ($u) => ['id' => $u->id, 'name' => $u->name, 'email' => $u->email])->all();
    }

    private function permissions(Request $r): array
    {
        return ['create' => $r->user()->hasPermission('admin.staff.work_schedules.create'), 'update' => $r->user()->hasPermission('admin.staff.work_schedules.update'), 'archive' => $r->user()->hasPermission('admin.staff.work_schedules.archive'), 'assignments' => $r->user()->hasPermission('admin.staff.work_schedules.manage_assignments'), 'exceptions' => $r->user()->hasPermission('admin.staff.work_schedules.manage_exceptions')];
    }

    private function scheduleData(WorkSchedule $s, bool $detailed = false): array
    {
        return ['id' => $s->id, 'name' => $s->name, 'description' => $s->description, 'timezone' => $s->timezone, 'is_active' => $s->is_active, 'deleted_at' => $s->deleted_at?->toIso8601String(), 'updated_at' => $s->updated_at?->toIso8601String(), 'assigned_agents_count' => $s->assigned_agents_count ?? $s->assignments?->count() ?? 0, 'intervals' => $s->intervals->map(fn ($i) => ['id' => $i->id, 'day_of_week' => $i->day_of_week->value, 'starts_at' => substr($i->starts_at, 0, 5), 'ends_at' => substr($i->ends_at, 0, 5), 'ends_next_day' => $i->ends_next_day])->values(), 'assignments' => $detailed ? $s->assignments->map(fn ($a) => ['id' => $a->id, 'agent' => ['id' => $a->agent->id, 'name' => $a->agent->name], 'effective_from' => $a->effective_from->toDateString(), 'effective_until' => $a->effective_until?->toDateString(), 'exceptions' => $a->exceptions])->values() : []];
    }
}
