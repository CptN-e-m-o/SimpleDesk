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
                'key' => 'problems',
                'panel' => 'agent',
                'type' => 'agent',
            ],
            [
                'label' => 'Problems',
                'sort_order' => 60,
            ]
        );

        $permissions = [
            [
                'key' => 'agent.contacts.create_change',
                'label' => 'Create change',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.contacts.view_changes',
                'label' => 'View changes',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.contacts.edit_change',
                'label' => 'Edit change',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_changes',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.contacts.edit_change_cab_applied',
                'label' => 'Edit change while CAB applied',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.edit_change',
                'sort_order' => 40,
            ],
            [
                'key' => 'agent.contacts.edit_change_assignee',
                'label' => 'Edit change assignee',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_changes',
                'sort_order' => 50,
            ],
            [
                'key' => 'agent.contacts.edit_change_status',
                'label' => 'Edit change status',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_changes',
                'sort_order' => 60,
            ],
            [
                'key' => 'agent.contacts.update_activity_duration',
                'label' => 'Update an activity duration',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_changes',
                'sort_order' => 70,
            ],
            [
                'key' => 'agent.contacts.delete_change',
                'label' => 'Delete change',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_changes',
                'sort_order' => 80,
            ],
            [
                'key' => 'agent.contacts.attach_detach_plannings',
                'label' => 'Attach or detach the plannings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_changes',
                'sort_order' => 90,
            ],
            [
                'key' => 'agent.contacts.apply_remove_cab',
                'label' => 'Apply or remove CAB',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_changes',
                'sort_order' => 100,
            ],
            [
                'key' => 'agent.contacts.actions_discussions',
                'label' => 'Actions on discussions',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_changes',
                'sort_order' => 110,
            ],
            [
                'key' => 'agent.contacts.add_edit_comment',
                'label' => 'Add or edit the comment',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.actions_discussions',
                'sort_order' => 120,
            ],
            [
                'key' => 'agent.contacts.delete_comment',
                'label' => 'Delete the comment',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.actions_discussions',
                'sort_order' => 130,
            ],
            [
                'key' => 'agent.contacts.export_schedule_report',
                'label' => 'Export/Schedule the report',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_changes',
                'sort_order' => 140,
            ],
            [
                'key' => 'agent.contacts.attach_detach_changes',
                'label' => 'Attach or detach the changes',
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
