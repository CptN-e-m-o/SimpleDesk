<?php

namespace Database\Seeders\Permissions\Agent\AgentPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAgentProblemsSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'problems',
                'panel' => 'agent',
                'type' => 'agent',
            ],
            [
                'label' => 'Problems',
                'sort_order' => 50,
            ]
        );

        $permissions = [
            [
                'key' => 'agent.problems.create',
                'label' => 'Create problem',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.problems.view',
                'label' => 'View problems',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.problems.edit',
                'label' => 'Edit problem',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.problems.view',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.problems.delete',
                'label' => 'Delete problem',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.problems.view',
                'sort_order' => 40,
            ],
            [
                'key' => 'agent.problems.close',
                'label' => 'Close problem',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.problems.view',
                'sort_order' => 50,
            ],
            [
                'key' => 'agent.problems.attach_or_detach_plannings',
                'label' => 'Attach or detach plannings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.problems.view',
                'sort_order' => 60,
            ],
            [
                'key' => 'agent.problems.export_or_schedule_report',
                'label' => 'Export or schedule report',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.problems.view',
                'sort_order' => 70,
            ],
            [
                'key' => 'agent.problems.attach_or_detach',
                'label' => 'Attach or detach problems',
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
