<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Roles\StoreRoleRequest;
use App\Http\Requests\Admin\Roles\UpdateRoleRequest;
use App\Http\Resources\Admin\RoleFormResource;
use App\Http\Resources\Admin\RoleResource;
use App\Models\Role;
use App\Services\Admin\RoleService;
use App\Support\Roles\RolePermissionOptions;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function __construct(
        protected RoleService $roleService,
        protected RolePermissionOptions $rolePermissionOptions,
    ) {}

    public function index(): Response
    {
        $roles = Role::query()
            ->withTrashed()
            ->orderBy('id')
            ->get();

        return Inertia::render('Admin/Roles/Index', [
            'roles' => RoleResource::collection($roles)->resolve(),
        ]);
    }

    public function create(string $type = 'user'): Response
    {
        abort_unless(in_array($type, ['user', 'agent'], true), 404);

        return Inertia::render('Admin/Roles/Create', [
            'type' => $type,
            'permissionPanels' => $this->rolePermissionOptions->options($type),
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $this->roleService->create($request->validated());

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function edit(Role $role): Response
    {
        $role->load('permissions:id,key,label,type,ui_type,parent_id');

        return Inertia::render('Admin/Roles/Edit', [
            'role' => RoleFormResource::make($role)->resolve(),
            'permissionPanels' => $this->rolePermissionOptions->options($role->type),
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $this->roleService->update($role, $request->validated());

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->is_system, 403);

        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    public function restore(Role $role): RedirectResponse
    {
        $role->restore();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role restored successfully.');
    }

    public function forceDelete(Role $role): RedirectResponse
    {
        abort_if($role->is_system, 403);

        $role->forceDelete();

        return redirect()
            ->route('admin.roles.index')
            ->with('success', 'Role permanently deleted successfully.');
    }
}
