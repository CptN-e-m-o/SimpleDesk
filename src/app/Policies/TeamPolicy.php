<?php

namespace App\Policies;

use App\Models\Admin\Team;
use App\Models\User;

class TeamPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function view(User $user, Team $team): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function update(User $user, Team $team): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function delete(User $user, Team $team): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function restore(User $user, Team $team): bool
    {
        return $user->hasPermission('admin.staff.manage_teams');
    }

    public function forceDelete(User $user, Team $team): bool
    {
        return $user->isSuperAdmin();
    }
}
