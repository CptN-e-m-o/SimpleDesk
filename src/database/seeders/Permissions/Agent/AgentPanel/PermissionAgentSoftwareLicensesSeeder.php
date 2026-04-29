<?php

namespace Database\Seeders\Permissions\Agent\AgentPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAgentSoftwareLicensesSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'software_licenses',
                'panel' => 'agent',
                'type' => 'agent',
            ],
            [
                'label' => 'Software Licenses',
                'sort_order' => 100,
            ]
        );

        $permissions = [
            [
                'key' => 'agent.contracts.create_software_license',
                'label' => 'Create software license',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.contracts.view_software_licenses',
                'label' => 'View software licenses',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.contracts.edit_software_licenses',
                'label' => 'Edit software licenses',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_software_licenses',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.contracts.delete_software_licenses',
                'label' => 'Delete software licenses',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_software_licenses',
                'sort_order' => 40,
            ],
            [
                'key' => 'agent.contracts.check_out_check_in_actions',
                'label' => 'Check-out/Check-in actions',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_software_licenses',
                'sort_order' => 50,
            ],
            [
                'key' => 'agent.contracts.export_schedule_report',
                'label' => 'Export/Schedule the report',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.view_software_licenses',
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
