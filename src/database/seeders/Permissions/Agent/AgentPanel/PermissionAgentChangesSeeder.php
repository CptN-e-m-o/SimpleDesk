<?php

namespace Database\Seeders\Permissions\Agent\AgentPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAgentChangesSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'changes',
                'panel' => 'agent',
                'type' => 'agent',
            ],
            [
                'label' => 'Changes',
                'sort_order' => 60,
            ]
        );

        $permissions = [
            [
                'key' => 'agent.changes.create',
                'label' => 'Create change',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.changes.view',
                'label' => 'View changes',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.changes.edit',
                'label' => 'Edit change',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.changes.view',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.changes.edit_while_cab_applied',
                'label' => 'Edit change while CAB is applied',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.changes.edit',
                'sort_order' => 40,
            ],
            [
                'key' => 'agent.changes.edit_assignee',
                'label' => 'Edit change assignee',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.changes.view',
                'sort_order' => 50,
            ],
            [
                'key' => 'agent.changes.edit_status',
                'label' => 'Edit change status',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.changes.view',
                'sort_order' => 60,
            ],
            [
                'key' => 'agent.changes.update_activity_duration',
                'label' => 'Update activity duration',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.changes.view',
                'sort_order' => 70,
            ],
            [
                'key' => 'agent.changes.delete',
                'label' => 'Delete change',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.changes.view',
                'sort_order' => 80,
            ],
            [
                'key' => 'agent.changes.attach_or_detach_plannings',
                'label' => 'Attach or detach plannings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.changes.view',
                'sort_order' => 90,
            ],
            [
                'key' => 'agent.changes.apply_or_remove_cab',
                'label' => 'Apply or remove CAB',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.changes.view',
                'sort_order' => 100,
            ],
            [
                'key' => 'agent.changes.discussions.actions',
                'label' => 'Actions on discussions',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.changes.view',
                'sort_order' => 110,
            ],
            [
                'key' => 'agent.changes.discussions.add_or_edit_comment',
                'label' => 'Add or edit comment',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.changes.discussions.actions',
                'sort_order' => 120,
            ],
            [
                'key' => 'agent.changes.discussions.delete_comment',
                'label' => 'Delete comment',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.changes.discussions.actions',
                'sort_order' => 130,
            ],
            [
                'key' => 'agent.changes.export_or_schedule_report',
                'label' => 'Export or schedule report',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.changes.view',
                'sort_order' => 140,
            ],
            [
                'key' => 'agent.changes.attach_or_detach',
                'label' => 'Attach or detach changes',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 150,
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
