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
                'key' => 'billing.owned_package',
                'label' => 'View owned package',
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
