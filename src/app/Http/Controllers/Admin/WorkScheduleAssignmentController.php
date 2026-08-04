<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkSchedules\WorkScheduleAssignmentRequest;
use App\Models\Admin\WorkSchedule;
use App\Models\Admin\WorkScheduleAssignment;
use App\Services\Admin\WorkSchedules\WorkScheduleAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class WorkScheduleAssignmentController extends Controller
{
    public function __construct(private readonly WorkScheduleAssignmentService $service) {}

    public function store(WorkScheduleAssignmentRequest $request, WorkSchedule $workSchedule): RedirectResponse
    {
        $data = $request->validated();
        $this->service->bulkAssign($workSchedule, $data['user_ids'], $data['effective_from'], $data['effective_until'] ?? null, $request->user()->id);

        return back()->with('success', 'Agents assigned successfully.');
    }

    public function update(WorkScheduleAssignmentRequest $request, WorkScheduleAssignment $assignment): RedirectResponse
    {
        $data = $request->validated();
        $this->service->end($assignment, $data['effective_until'] ?? $data['effective_from'], $request->user()->id);

        return back()->with('success', 'Assignment updated.');
    }

    public function end(Request $request, WorkScheduleAssignment $assignment): RedirectResponse
    {
        $request->validate(['effective_until' => ['required', 'date']]);
        $this->service->end($assignment, $request->string('effective_until')->toString(), $request->user()->id);

        return back()->with('success', 'Assignment ended.');
    }

    public function destroy(WorkScheduleAssignment $assignment): RedirectResponse
    {
        $this->service->deleteFuture($assignment);

        return back()->with('success', 'Future assignment deleted.');
    }
}
