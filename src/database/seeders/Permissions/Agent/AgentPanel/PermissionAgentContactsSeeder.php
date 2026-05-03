<?php

namespace Database\Seeders\Permissions\Agent\AgentPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAgentContactsSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'contacts',
                'panel' => 'agent',
                'type' => 'agent',
            ],
            [
                'label' => 'Contacts',
                'sort_order' => 20,
            ]
        );

        $permissions = [
            [
                'key' => 'agent.contacts.view',
                'label' => 'View contacts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.contacts.create_or_edit',
                'label' => 'Create or edit contact',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.contacts.actions',
                'label' => 'Actions on contact',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.contacts.change_role',
                'label' => 'Change role',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.actions',
                'sort_order' => 40,
            ],
            [
                'key' => 'agent.contacts.switch_to_user_roles',
                'label' => 'Switch to user roles',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.change_role',
                'sort_order' => 50,
            ],
            [
                'key' => 'agent.contacts.switch_to_agent_roles',
                'label' => 'Switch to agent roles',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.change_role',
                'sort_order' => 60,
            ],
            [
                'key' => 'agent.contacts.change_password',
                'label' => 'Change password',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.actions',
                'sort_order' => 70,
            ],
            [
                'key' => 'agent.contacts.change_user_password',
                'label' => 'Change password for users',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.change_password',
                'sort_order' => 80,
            ],
            [
                'key' => 'agent.contacts.change_agent_password',
                'label' => 'Change password for agents',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.change_password',
                'sort_order' => 90,
            ],
            [
                'key' => 'agent.contacts.deactivate_account',
                'label' => 'Deactivate account',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.actions',
                'sort_order' => 100,
            ],
            [
                'key' => 'agent.contacts.deactivate_user_account',
                'label' => 'Deactivate user accounts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.deactivate_account',
                'sort_order' => 110,
            ],
            [
                'key' => 'agent.contacts.deactivate_agent_account',
                'label' => 'Deactivate agent accounts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.deactivate_account',
                'sort_order' => 120,
            ],
            [
                'key' => 'agent.contacts.restore_account',
                'label' => 'Restore account',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.actions',
                'sort_order' => 130,
            ],
            [
                'key' => 'agent.contacts.restore_user_account',
                'label' => 'Restore user accounts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.restore_account',
                'sort_order' => 140,
            ],
            [
                'key' => 'agent.contacts.restore_agent_account',
                'label' => 'Restore agent accounts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.restore_account',
                'sort_order' => 150,
            ],
            [
                'key' => 'agent.contacts.delete_account',
                'label' => 'Delete account',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.actions',
                'sort_order' => 160,
            ],
            [
                'key' => 'agent.contacts.delete_user_account',
                'label' => 'Delete user accounts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.delete_account',
                'sort_order' => 170,
            ],
            [
                'key' => 'agent.contacts.delete_agent_account',
                'label' => 'Delete agent accounts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.delete_account',
                'sort_order' => 180,
            ],
            [
                'key' => 'agent.contacts.disable_2fa',
                'label' => 'Disable 2FA',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.actions',
                'sort_order' => 190,
            ],
            [
                'key' => 'agent.contacts.export',
                'label' => 'Export contacts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view',
                'sort_order' => 200,
            ],
            [
                'key' => 'agent.contacts.merge',
                'label' => 'Merge contacts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view',
                'sort_order' => 210,
            ],
            [
                'key' => 'agent.organizations.view',
                'label' => 'View organizations',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 220,
            ],
            [
                'key' => 'agent.organizations.create_or_edit',
                'label' => 'Create or edit organization',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.organizations.view',
                'sort_order' => 230,
            ],
            [
                'key' => 'agent.organizations.actions',
                'label' => 'Actions on organization',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.organizations.view',
                'sort_order' => 240,
            ],
            [
                'key' => 'agent.organizations.add_manager',
                'label' => 'Add manager',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.organizations.actions',
                'sort_order' => 250,
            ],
            [
                'key' => 'agent.organizations.link_or_unlink_contacts',
                'label' => 'Link or unlink contacts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.organizations.actions',
                'sort_order' => 260,
            ],
            [
                'key' => 'agent.organizations.delete',
                'label' => 'Delete organization',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.organizations.view',
                'sort_order' => 270,
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
