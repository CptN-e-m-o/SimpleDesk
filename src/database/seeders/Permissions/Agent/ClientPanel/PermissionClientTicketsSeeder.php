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
                'key' => 'billing',
                'panel' => 'client',
                'type' => 'agent',
            ],
            [
                'label' => 'Billing',
                'sort_order' => 10,
            ]
        );

        $permissions = [
            [
                'key' => 'agent.billing.owned_package',
                'label' => 'View owned package',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.billing.purchase_package',
                'label' => 'Purchase package',
                'type' => 'agent',
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
                    'parent_id' => null,
                ]
            );
        }
    }
}
