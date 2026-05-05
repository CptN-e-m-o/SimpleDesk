<?php

namespace Database\Seeders\Permissions\Agent\AdminPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAdminGeneralSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'general',
                'panel' => 'admin',
                'type' => 'agent',
            ],
            [
                'label' => 'General',
                'sort_order' => 10,
            ]
        );

        $permissions = [
            [
                'key' => 'admin.general.manage_emails',
                'label' => 'Manage email settings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'admin.general.manage_drivers_integrations',
                'label' => 'Manage driver integrations',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'admin.general.manage_developer_settings',
                'label' => 'Manage Developer Settings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 30,
            ],
            [
                'key' => 'admin.general.manage_notify',
                'label' => 'Manage notifications',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 40,
            ],
            [
                'key' => 'admin.general.manage_debugging_options',
                'label' => 'Manage debugging options',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 50,
            ],
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
