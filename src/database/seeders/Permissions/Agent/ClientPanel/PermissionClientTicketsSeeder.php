<?php

namespace Database\Seeders\Permissions\Agent\ClientPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionClientTicketsSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'tickets',
                'panel' => 'client',
                'type' => 'agent',
            ],
            [
                'label' => 'Clients',
                'sort_order' => 10,
            ]
        );

        $permissions = [
            [
                'key' => 'client.agent.tickets.create',
                'label' => 'Create ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'client.agent.tickets.respond',
                'label' => 'Respond a ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'client.agent.tickets.change_status',
                'label' => 'Change status',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 30,
            ],
            [
                'key' => 'client.agent.tickets.visibility',
                'label' => 'Tickets visibility',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 40,
            ],
            [
                'key' => 'client.agent.tickets.visibility.requester',
                'label' => 'View Requester Tickets',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'client.agent.tickets.visibility',
                'sort_order' => 50,
            ],
            [
                'key' => 'client.agent.tickets.collaborator.view',
                'label' => 'Show tickets where I’m added as a collaborator',
                'type' => 'agent',
                'ui_type' => 'checkbox',
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
