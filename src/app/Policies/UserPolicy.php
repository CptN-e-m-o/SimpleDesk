<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_agents');
    }

    public function view(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_agents');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_agents');
    }

    public function update(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_agents');
    }

    public function delete(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_agents');
    }

    public function restore(User $user): bool
    {
        return $user->hasPermission('admin.staff.manage_agents');
    }

    public function forceDelete(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
