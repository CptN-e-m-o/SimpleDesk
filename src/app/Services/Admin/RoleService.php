<?php

namespace App\Services\Admin;

use App\Models\Role;
use Illuminate\Support\Str;

class RoleService
{
    public function create(array $data): Role
    {
        $permissionIds = $data['permission_ids'] ?? [];
        unset($data['permission_ids']);

        $data['name'] = $data['name'] ?: Str::slug($data['label'], '_');
        $data['is_system'] = false;

        $role = Role::create($data);
        $role->permissions()->sync($permissionIds);

        return $role;
    }

    public function update(Role $role, array $data): Role
    {
        $permissionIds = $data['permission_ids'] ?? [];
        unset($data['permission_ids']);

        if ($role->is_system) {
            unset($data['type'], $data['is_default']);
        }

        $role->update($data);
        $role->permissions()->sync($permissionIds);

        return $role;
    }
}
