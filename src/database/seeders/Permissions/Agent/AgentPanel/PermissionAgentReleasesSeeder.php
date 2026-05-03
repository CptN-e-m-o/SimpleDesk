<?php

namespace Database\Seeders\Permissions\Agent\AgentPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAgentReleasesSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'releases',
                'panel' => 'agent',
                'type' => 'agent',
            ],
            [
                'label' => 'Releases',
                'sort_order' => 70,
            ]
        );

        $permissions = [
            [
                'key' => 'agent.releases.create',
                'label' => 'Create release',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.releases.view',
                'label' => 'View releases',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.releases.edit',
                'label' => 'Edit release',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.releases.view',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.releases.mark_complete',
                'label' => 'Mark as complete',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.releases.view',
                'sort_order' => 40,
            ],
            [
                'key' => 'agent.releases.delete',
                'label' => 'Delete release',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.releases.view',
                'sort_order' => 50,
            ],
            [
                'key' => 'agent.releases.attach_or_detach_plannings',
                'label' => 'Attach or detach plannings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.releases.view',
                'sort_order' => 60,
            ],
            [
                'key' => 'agent.releases.export_or_schedule_report',
                'label' => 'Export or schedule report',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.releases.view',
                'sort_order' => 70,
            ],
            [
                'key' => 'agent.releases.attach_or_detach',
                'label' => 'Attach or detach releases',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 80,
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
