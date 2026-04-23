<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Departments\StoreDepartmentRequest;
use App\Http\Requests\Admin\Departments\UpdateDepartmentRequest;
use App\Http\Resources\Admin\DepartmentFormResource;
use App\Http\Resources\Admin\DepartmentResource;
use App\Models\Admin\Department;
use App\Services\Admin\DepartmentService;
use App\Support\Departments\DepartmentStatusOptions;
use App\Support\Departments\DepartmentTeamOptions;
use App\Support\Departments\DepartmentUserOptions;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class DepartmentController extends Controller
{
    public function __construct(
        protected DepartmentService $departmentService,
        protected DepartmentUserOptions $departmentUserOptions,
        protected DepartmentTeamOptions $departmentTeamOptions,
        protected DepartmentStatusOptions $departmentStatusOptions,
    ) {}

    public function index(): Response
    {
        $departments = Department::query()
            ->withTrashed()
            ->with([
                'managers:id,name,email',
                'status:id,name,color,code',
            ])
            ->latest()
            ->get();

        return Inertia::render('Admin/Departments/Index', [
            'departments' => DepartmentResource::collection($departments)->resolve(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Departments/Create', [
            'users' => $this->departmentUserOptions->options(),
            'teams' => $this->departmentTeamOptions->options(),
            'statuses' => $this->departmentStatusOptions->options(),
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $this->departmentService->create($request->validated());

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department created successfully.');
    }

    public function show(Department $department): Response
    {
        $department->load([
            'status:id,name,code,color',
            'managers:id,name,email',
            'teams:id,name',
            'users:id,name,email',
        ]);

        return Inertia::render('Admin/Departments/Show', [
            'department' => DepartmentResource::make($department)->resolve(),
        ]);
    }

    public function edit(Department $department): Response
    {
        $department->load([
            'managers:id,name,email',
            'teams:id,name',
        ]);

        return Inertia::render('Admin/Departments/Edit', [
            'department' => DepartmentFormResource::make($department)->resolve(),
            'users' => $this->departmentUserOptions->options(),
            'teams' => $this->departmentTeamOptions->options(),
            'statuses' => $this->departmentStatusOptions->options(),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $this->departmentService->update($department, $request->validated());

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department updated successfully.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        $department->delete();

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department deleted successfully.');
    }

    public function restore(Department $department): RedirectResponse
    {
        $department->restore();

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department restored successfully.');
    }

    public function forceDelete(Department $department): RedirectResponse
    {
        $department->forceDelete();

        return redirect()
            ->route('admin.departments.index')
            ->with('success', 'Department permanently deleted successfully.');
    }
}
