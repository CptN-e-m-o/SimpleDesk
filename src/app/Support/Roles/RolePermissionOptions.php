<?php

namespace App\Support\Roles;

use App\Models\Permission;
use App\Models\PermissionGroup;

class RolePermissionOptions
{
    public function options(string $type): array
    {
        $panelLabels = [
            'admin' => 'Admin Panel',
            'agent' => 'Agent Panel',
            'client' => 'Client Panel',
            'general' => 'General',
        ];

        $panelSort = [
            'admin' => 10,
            'agent' => 20,
            'client' => 30,
            'general' => 40,
        ];

        return PermissionGroup::query()
            ->where('type', $type)
            ->with([
                'permissions' => fn ($query) => $query
                    ->whereNull('parent_id')
                    ->with('childrenRecursive')
                    ->orderBy('sort_order')
                    ->orderBy('id'),
            ])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('panel')
            ->sortBy(fn ($groups, string $panel) => $panelSort[$panel] ?? 999)
            ->map(fn ($groups, string $panel) => [
                'key' => $panel,
                'label' => $panelLabels[$panel] ?? ucfirst($panel),
                'groups' => $groups
                    ->sortBy([
                        ['sort_order', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->map(fn ($group) => [
                        'id' => $group->id,
                        'key' => $group->key,
                        'label' => $group->label,
                        'panel' => $group->panel,
                        'type' => $group->type,
                        'permissions' => $group->permissions
                            ->map(fn (Permission $permission) => $this->mapPermission($permission))
                            ->values(),
                    ])
                    ->values(),
            ])
            ->values()
            ->all();
    }

    private function mapPermission(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'key' => $permission->key,
            'label' => $permission->label,
            'type' => $permission->type,
            'ui_type' => $permission->ui_type,
            'description' => $permission->description,
            'parent_id' => $permission->parent_id,
            'children' => $permission->childrenRecursive
                ->map(fn (Permission $child) => $this->mapPermission($child))
                ->values(),
        ];
    }
}
