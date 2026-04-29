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
                'key' => 'agent.contacts.create_asset',
                'label' => 'Create asset',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.contacts.view_assets',
                'label' => 'View assets',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.contacts.edit_asset',
                'label' => 'Edit asset',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_assets',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.contacts.delete_asset',
                'label' => 'Delete asset',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_assets',
                'sort_order' => 40,
            ],
            [
                'key' => 'agent.contacts.export_schedule_report',
                'label' => 'Export/Schedule the report',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_assets',
                'sort_order' => 50,
            ],
            [
                'key' => 'agent.contacts.attach_detach_assets',
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
