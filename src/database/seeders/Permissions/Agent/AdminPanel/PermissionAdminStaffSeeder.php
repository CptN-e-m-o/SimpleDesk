<?php

namespace Database\Seeders\Permissions\Agent\AdminPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAdminStaffSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'staff',
                'panel' => 'admin',
                'type' => 'agent',
            ],
            [
                'label' => 'Staff',
                'sort_order' => 20,
            ]
        );

        $permissions = [
            [
                'key' => 'admin.staff.manage_agents',
                'label' => 'Manage agents',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'admin.staff.manage_roles',
                'label' => 'Manage roles',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'admin.staff.manage_departments',
                'label' => 'Manage departments',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 30,
            ],
            [
                'key' => 'admin.staff.assign_as_department_manager',
                'label' => 'Can be assigned as department manager',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 40,
            ],
            [
                'key' => 'admin.staff.manage_teams',
                'label' => 'Manage teams',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 50,
            ],
            [
                'key' => 'admin.staff.assign_to_team',
                'label' => 'Can be assigned to teams',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 60,
            ],
            ['key' => 'admin.staff.work_schedules.view', 'label' => 'View work schedules', 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 70],
            ['key' => 'admin.staff.work_schedules.create', 'label' => 'Create work schedules', 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 80],
            ['key' => 'admin.staff.work_schedules.update', 'label' => 'Update work schedules', 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 90],
            ['key' => 'admin.staff.work_schedules.archive', 'label' => 'Archive and restore work schedules', 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 100],
            ['key' => 'admin.staff.work_schedules.manage_assignments', 'label' => 'Manage work schedule assignments', 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 110],
            ['key' => 'admin.staff.work_schedules.manage_exceptions', 'label' => 'Manage work schedule exceptions', 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 120],
        ];

        foreach ($permissions as $permission) {
            $parentKey = $permission['parent_key'] ?? null;

            unset($permission['parent_key']);

            $parentId = null;

            if ($parentKey) {
                $parentId = Permission::where('key', $parentKey)->value('id');
            }

            Permission::updateOrCreate(
                ['key' => $permission['key']],
                [
                    ...$permission,
                    'permission_group_id' => $group->id,
                    'parent_id' => $parentId,
                ]
            );
        }
    }
}
