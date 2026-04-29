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
                'key' => 'agent.contacts.create_problem',
                'label' => 'Create problem',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.contacts.view_problems',
                'label' => 'View problems',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.contacts.edit_problem',
                'label' => 'Edit problem',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_problems',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.contacts.delete_problem',
                'label' => 'Delete problem',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_problems',
                'sort_order' => 40,
            ],
            [
                'key' => 'agent.contacts.close_problem',
                'label' => 'Close problem',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_problems',
                'sort_order' => 50,
            ],
            [
                'key' => 'agent.contacts.attach_detach_plannings',
                'label' => 'Attach or detach plannings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_problems',
                'sort_order' => 60,
            ],
            [
                'key' => 'agent.contacts.export_schedule_report',
                'label' => 'Export/Schedule the report',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_problems',
                'sort_order' => 70,
            ],
            [
                'key' => 'agent.contacts.attach_detach_problems',
                'label' => 'Attach or detach the problems',
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
