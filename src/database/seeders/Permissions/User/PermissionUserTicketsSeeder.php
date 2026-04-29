<?php

namespace Database\Seeders\Permissions\User;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionUserTicketsSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'tickets',
                'panel' => 'client',
                'type' => 'user',
            ],
            [
                'label' => 'Tickets',
                'sort_order' => 20,
            ]
        );

        $permissions = [
            [
                'key' => 'tickets.create',
                'label' => 'Create a Ticket',
                'type' => 'user',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'tickets.create_with_organization_assets',
                'label' => 'Organization members can attach their organization
                assets while creating a ticket with this permission',
                'type' => 'user',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'billing.owned_package',
                'label' => 'Shows their owned package',
                'type' => 'user',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'billing.purchase_package',
                'label' => 'Purchase package',
                'type' => 'user',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'billing.owned_package',
                'label' => 'Shows their owned package',
                'type' => 'user',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'billing.purchase_package',
                'label' => 'Purchase package',
                'type' => 'user',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'billing.owned_package',
                'label' => 'Shows their owned package',
                'type' => 'user',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'billing.purchase_package',
                'label' => 'Purchase package',
                'type' => 'user',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate(
                ['key' => $permission['key']],
                [
                    ...$permission,
                    'permission_group_id' => $group->id,
                ]
            );
        }
    }
}
