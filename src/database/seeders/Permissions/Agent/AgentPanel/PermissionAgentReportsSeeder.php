<?php

namespace Database\Seeders\Permissions\Agent\AgentPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAgentReportsSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'reports',
                'panel' => 'agent',
                'type' => 'agent',
            ],
            [
                'label' => 'Reports',
                'sort_order' => 40,
            ]
        );

        $permissions = [
            [
                'key' => 'agent.contacts.report_analytics',
                'label' => 'Report analytics',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.contacts.helpdesk_reports',
                'label' => 'Helpdesk reports',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.report_analytics',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.contacts.servicedesk_reports',
                'label' => 'ServiceDesk reports',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.contacts.report_analytics',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.contacts.settings',
                'label' => 'Settings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 40,
            ],
            [
                'key' => 'agent.contacts.access_agent_reports',
                'label' => 'Access all agent reports from the activity downloads',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 50,
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
