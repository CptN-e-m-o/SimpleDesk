<?php

namespace Database\Seeders\Permissions\Agent\AdminPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAdminAddOnsSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'add_ons',
                'panel' => 'admin',
                'type' => 'agent',
            ],
            [
                'label' => 'Add Ons',
                'sort_order' => 60,
            ]
        );

        $permissions = [
            [
                'key' => 'admin.add_ons.manage_plugins',
                'label' => 'Manage Plugins',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'admin.add_ons.manage_modules',
                'label' => 'Manage Modules',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'admin.add_ons.manage_modules.billing',
                'label' => 'Manage Billing',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'admin.add_ons.manage_modules',
                'sort_order' => 30,
            ],
            [
                'key' => 'admin.add_ons.manage_modules.timetrack',
                'label' => 'Manage TimeTrack',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'admin.add_ons.manage_modules',
                'sort_order' => 40,
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
