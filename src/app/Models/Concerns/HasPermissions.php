<?php

namespace App\Models\Concerns;

use App\Models\Permission;

trait HasPermissions
{
    public function hasPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('permissions.key', $permission);
            })
            ->exists();
    }

    public function permissionKeys(): array
    {
        if ($this->isSuperAdmin()) {
            return Permission::query()
                ->pluck('key')
                ->unique()
                ->values()
                ->all();
        }

        return $this->roles()
            ->with('permissions:id,key')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('key')
            ->unique()
            ->values()
            ->all();
    }

    public function isSuperAdmin(): bool
    {
        return $this->roles()
            ->where('roles.name', 'super_admin')
            ->exists();
    }
}
