<?php

namespace Database\Seeders\Permissions\User;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionUserBillingSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'billing',
                'panel' => 'client',
                'type' => 'user',
            ],
            [
                'label' => 'Billing',
                'sort_order' => 10,
            ]
        );

        $permissions = [
            [
                'key' => 'tickets.create',
                'label' => 'Create ticket',
                'type' => 'user',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'tickets.create_with_organization_assets',
                'label' => 'Organization members can attach their organization assets while creating a ticket with this permission',
                'type' => 'user',
                'ui_type' => 'checkbox',
                'parent_key' => 'tickets.create',
                'sort_order' => 20,
            ],
            [
                'key' => 'tickets.respond',
                'label' => 'Respond a ticket',
                'type' => 'user',
                'ui_type' => 'checkbox',
                'sort_order' => 30,
            ],
            [
                'key' => 'tickets.change_status',
                'label' => 'Change status',
                'type' => 'user',
                'ui_type' => 'checkbox',
                'sort_order' => 40,
            ],
            [
                'key' => 'tickets.visibility',
                'label' => 'Tickets visibility',
                'type' => 'user',
                'ui_type' => 'checkbox',
                'sort_order' => 50,
            ],
            [
                'key' => 'tickets.visibility.requester',
                'label' => 'View Requester Tickets',
                'type' => 'user',
                'ui_type' => 'radio',
                'parent_key' => 'tickets.visibility',
                'sort_order' => 60,
            ],
            [
                'key' => 'tickets.visibility.organization',
                'label' => 'View Organization Tickets',
                'type' => 'user',
                'ui_type' => 'radio',
                'parent_key' => 'tickets.visibility',
                'sort_order' => 70,
            ],
            [
                'key' => 'tickets.collaborator.view',
                'label' => 'Show tickets where I’m added as a collaborator',
                'type' => 'user',
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
