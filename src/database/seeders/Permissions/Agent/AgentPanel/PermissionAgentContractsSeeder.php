<?php

namespace Database\Seeders\Permissions\Agent\AgentPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAgentContractsSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'contracts',
                'panel' => 'agent',
                'type' => 'agent',
            ],
            [
                'label' => 'Contracts',
                'sort_order' => 90,
            ]
        );

        $permissions = [
            [
                'key' => 'agent.contracts.create',
                'label' => 'Create contract',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.contracts.create_type',
                'label' => 'Create contract type',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contracts.create',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.contracts.create_vendor',
                'label' => 'Create vendor',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contracts.create',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.contracts.view',
                'label' => 'View contracts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 40,
            ],
            [
                'key' => 'agent.contracts.edit',
                'label' => 'Edit contract',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contracts.view',
                'sort_order' => 50,
            ],
            [
                'key' => 'agent.contracts.delete',
                'label' => 'Delete contract',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contracts.view',
                'sort_order' => 60,
            ],
            [
                'key' => 'agent.contracts.terminate',
                'label' => 'Terminate contract',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contracts.view',
                'sort_order' => 70,
            ],
            [
                'key' => 'agent.contracts.extend_or_renew',
                'label' => 'Extend or renew contract',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contracts.view',
                'sort_order' => 80,
            ],
            [
                'key' => 'agent.contracts.set_expiry_reminder',
                'label' => 'Set expiry reminder',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contracts.view',
                'sort_order' => 90,
            ],
            [
                'key' => 'agent.contracts.export_or_schedule_report',
                'label' => 'Export or schedule report',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contracts.view',
                'sort_order' => 100,
            ],
            [
                'key' => 'agent.contracts.attach_or_detach',
                'label' => 'Attach or detach contracts',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 110,
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
