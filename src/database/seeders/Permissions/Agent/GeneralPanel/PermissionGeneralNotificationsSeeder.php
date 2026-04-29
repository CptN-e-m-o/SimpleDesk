<?php

namespace Database\Seeders\Permissions\Agent\GeneralPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionGeneralNotificationsSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'notifications',
                'panel' => 'general',
                'type' => 'agent',
            ],
            [
                'label' => 'Notifications',
                'sort_order' => 30,
            ]
        );

        $permissions = [
            [
                'key' => 'general.notifications.notification',
                'label' => 'Notification',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'general.notifications.receive_agent_notifications',
                'label' => 'Receive agent notifications',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'general.notifications.notification',
                'sort_order' => 20,
            ],
            [
                'key' => 'general.notifications.receive_admin_notifications',
                'label' => 'Receive admin notifications',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'general.notifications.notification',
                'sort_order' => 30,
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
