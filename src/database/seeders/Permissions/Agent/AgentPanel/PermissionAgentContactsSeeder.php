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
                'key' => 'agent.contacts.view_contacts',
                'label' => 'View contacts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.contacts.create_or_edit_contact',
                'label' => 'Create or edit contact',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_contacts',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.contacts.actions_on_contact',
                'label' => 'Actions on contact',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_contacts',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.contacts.change_role',
                'label' => 'Change role',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.actions_on_contact',
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
                'key' => 'agent.contacts.switch_agent_roles',
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
                'parent_key' => 'agent.contacts.actions_on_contact',
                'sort_order' => 70,
            ],
            [
                'key' => 'agent.contacts.change_password_user',
                'label' => 'Change the password for a user (who has only client panel permissions)',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.change_password',
                'sort_order' => 80,
            ],
            [
                'key' => 'agent.contacts.change_password_agent',
                'label' => 'Change the password for an agent (who has agent panel permissions)',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.change_password',
                'sort_order' => 90,
            ],
            [
                'key' => 'agent.contacts.deactivate_the_account',
                'label' => 'Change password',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.actions_on_contact',
                'sort_order' => 100,
            ],
            [
                'key' => 'agent.contacts.deactivate_account_user',
                'label' => 'Deactivate the account for a user (who has only client panel permissions)',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.deactivate_the_account',
                'sort_order' => 110,
            ],
            [
                'key' => 'agent.contacts.deactivate_account_agent',
                'label' => 'Deactivate the account for an agent (who has agent panel permissions)',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.deactivate_the_account',
                'sort_order' => 120,
            ],
            [
                'key' => 'agent.contacts.restore_account',
                'label' => 'Change password',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.actions_on_contact',
                'sort_order' => 130,
            ],
            [
                'key' => 'agent.contacts.restore_account_user',
                'label' => 'Restore the account for a user (who has only client panel permissions)',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.restore_the_account',
                'sort_order' => 140,
            ],
            [
                'key' => 'agent.contacts.restore_account_agent',
                'label' => 'Restore the account for an agent (who has agent panel permissions)',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.restore_the_account',
                'sort_order' => 150,
            ],
            [
                'key' => 'agent.contacts.delete_account',
                'label' => 'Delete the account',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.actions_on_contact',
                'sort_order' => 160,
            ],
            [
                'key' => 'agent.contacts.delete_account_user',
                'label' => 'Delete the account for a user (who has only client panel permissions)',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.delete_the_account',
                'sort_order' => 170,
            ],
            [
                'key' => 'agent.contacts.delete_account_agent',
                'label' => 'Delete the account for an agent (who has agent panel permissions)',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.delete_the_account',
                'sort_order' => 180,
            ],
            [
                'key' => 'agent.contacts.2fa_disable',
                'label' => '2FA Disable',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.actions_on_contact',
                'sort_order' => 190,
            ],
            [
                'key' => 'agent.contacts.export_contacts',
                'label' => 'Export Contacts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_contacts',
                'sort_order' => 200,
            ],
            [
                'key' => 'agent.contacts.merge_contacts',
                'label' => 'Merge Contacts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_contacts',
                'sort_order' => 210,
            ],
            [
                'key' => 'agent.contacts.view_organizations',
                'label' => 'View organizations',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 220,
            ],
            [
                'key' => 'agent.contacts.create_edit_organization',
                'label' => 'Create or edit organization',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_organizations',
                'sort_order' => 230,
            ],
            [
                'key' => 'agent.contacts.actions_organization',
                'label' => 'Actions on organization',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_organizations',
                'sort_order' => 240,
            ],
            [
                'key' => 'agent.contacts.add_manager',
                'label' => 'Add manager',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.actions_on_organization',
                'sort_order' => 250,
            ],
            [
                'key' => 'agent.contacts.link_unlink_contacts',
                'label' => 'Link or unlink contacts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.actions_on_organization',
                'sort_order' => 260,
            ],
            [
                'key' => 'agent.contacts.delete_organization',
                'label' => 'Delete organization',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_organizations',
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
