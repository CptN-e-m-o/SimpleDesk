<?php

namespace Database\Seeders\Permissions\Agent\AgentPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAgentAssetsSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'assets',
                'panel' => 'agent',
                'type' => 'agent',
            ],
            [
                'label' => 'Assets',
                'sort_order' => 80,
            ]
        );

        $permissions = [
            [
                'key' => 'agent.assets.create',
                'label' => 'Create asset',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.assets.view',
                'label' => 'View assets',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.assets.edit',
                'label' => 'Edit asset',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.assets.view',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.assets.delete',
                'label' => 'Delete asset',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.assets.view',
                'sort_order' => 40,
            ],
            [
                'key' => 'agent.assets.export_or_schedule_report',
                'label' => 'Export or schedule report',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.assets.view',
                'sort_order' => 50,
            ],
            [
                'key' => 'agent.assets.attach_or_detach',
                'label' => 'Attach or detach assets',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 60,
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
