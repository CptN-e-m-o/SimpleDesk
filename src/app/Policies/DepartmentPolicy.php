<?php

namespace App\Policies;

use App\Models\Admin\Department;
use App\Models\User;

class DepartmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function view(User $user, Department $department): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function update(User $user, Department $department): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function delete(User $user, Department $department): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function restore(User $user, Department $department): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function forceDelete(User $user, Department $department): bool
    {
        return $user->isSuperAdmin();
    }
}
