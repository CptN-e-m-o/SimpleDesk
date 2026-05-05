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
                'key' => 'agent.software_licenses.create',
                'label' => 'Create software license',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.software_licenses.view',
                'label' => 'View software licenses',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.software_licenses.edit',
                'label' => 'Edit software license',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.software_licenses.view',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.software_licenses.delete',
                'label' => 'Delete software license',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.software_licenses.view',
                'sort_order' => 40,
            ],
            [
                'key' => 'agent.software_licenses.check_in_out',
                'label' => 'Check-out/check-in actions',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.software_licenses.view',
                'sort_order' => 50,
            ],
            [
                'key' => 'agent.software_licenses.export_or_schedule_report',
                'label' => 'Export or schedule report',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.software_licenses.view',
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
