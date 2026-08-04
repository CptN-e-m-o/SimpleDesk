<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Admin\WorkScheduleExceptionType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WorkSchedules\WorkScheduleExceptionRequest;
use App\Models\Admin\WorkScheduleAssignment;
use App\Models\Admin\WorkScheduleException;
use App\Services\Admin\WorkSchedules\WorkScheduleExceptionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class WorkScheduleExceptionController extends Controller
{
    public function __construct(
        private readonly WorkScheduleExceptionService $service
    ) {
    }

    public function index(
        WorkScheduleAssignment $assignment
    ): Response {
        $assignment->load([
            'agent',
            'schedule',
            'exceptions.intervals',
        ]);

        return Inertia::render(
            'Admin/WorkSchedules/Exceptions',
            [
                'assignment' => [
                    'id' => $assignment->id,
                    'agent' => [
                        'id' => $assignment->agent->id,
                        'name' => $assignment->agent->name,
                    ],
                    'schedule' => [
                        'id' => $assignment->schedule->id,
                        'name' => $assignment->schedule->name,
                        'timezone' => $assignment->schedule->timezone,
                    ],
                    'effective_from' => $assignment
                        ->effective_from
                        ->toDateString(),
                    'effective_until' => $assignment
                        ->effective_until
                        ?->toDateString(),
                    'exceptions' => $assignment
                        ->exceptions
                        ->map(
                            fn (WorkScheduleException $exception) => [
                                'id' => $exception->id,
                                'date' => $exception
                                    ->date
                                    ->toDateString(),
                                'type' => $exception->type->value,
                                'reason' => $exception->reason,
                                'intervals' => $exception
                                    ->intervals
                                    ->map(
                                        fn ($interval) => [
                                            'starts_at' => substr(
                                                $interval->starts_at,
                                                0,
                                                5
                                            ),
                                            'ends_at' => substr(
                                                $interval->ends_at,
                                                0,
                                                5
                                            ),
                                            'ends_next_day' => $interval
                                                ->ends_next_day,
                                        ]
                                    )
                                    ->values(),
                            ]
                        )
                        ->values(),
                ],
                'types' => array_map(
                    fn (WorkScheduleExceptionType $type) => $type->value,
                    WorkScheduleExceptionType::cases()
                ),
            ]
        );
    }

    public function store(
        WorkScheduleExceptionRequest $request,
        WorkScheduleAssignment $assignment
    ): RedirectResponse {
        $this->service->create(
            $assignment,
            $request->validated(),
            $request->user()->id
        );

        return redirect()
            ->route(
                'admin.work-schedule-exceptions.index',
                $assignment->id
            )
            ->with(
                'success',
                'Schedule exception created.'
            );
    }

    public function update(
        WorkScheduleExceptionRequest $request,
        WorkScheduleException $exception
    ): RedirectResponse {
        $assignmentId = $exception->work_schedule_assignment_id;

        $this->service->update(
            $exception,
            $request->validated(),
            $request->user()->id
        );

        return redirect()
            ->route(
                'admin.work-schedule-exceptions.index',
                $assignmentId
            )
            ->with(
                'success',
                'Schedule exception updated.'
            );
    }

    public function destroy(
        WorkScheduleException $exception
    ): RedirectResponse {
        $assignmentId = $exception->work_schedule_assignment_id;

        $exception->delete();

        return redirect()
            ->route(
                'admin.work-schedule-exceptions.index',
                $assignmentId
            )
            ->with(
                'success',
                'Schedule exception deleted.'
            );
    }
}
