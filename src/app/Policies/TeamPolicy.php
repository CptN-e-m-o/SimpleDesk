<?php

namespace App\Policies;

use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function view(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function update(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function restore(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function forceDelete(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
