<?php

namespace Database\Seeders\Permissions\Agent\GeneralPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionGeneralVisibilitySeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'visibility',
                'panel' => 'general',
                'type' => 'agent',
            ],
            [
                'label' => 'Visibility',
                'sort_order' => 10,
            ]
        );

        $permissions = [
            [
                'key' => 'agent.general.visibility.show_health_alert_icon',
                'label' => 'Show the health alert icon',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.general.visibility.display_all_labels',
                'label' => 'Display all labels (even if not assigned to a department manager or team lead)',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['key' => $permission['key']],
                [
                    ...$permission,
                    'permission_group_id' => $group->id,
                    'parent_id' => null,
                ]
            );
        }
    }
}
