<?php

namespace Database\Seeders\Permissions\Agent\GeneralPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionGeneralNetworkDiscoverySeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'network_discovery',
                'panel' => 'general',
                'type' => 'agent',
            ],
            [
                'label' => 'Network Discovery',
                'sort_order' => 40,
            ]
        );

        $permissions = [
            [
                'key' => 'agent.general.network_discovery.sync_detected_assets',
                'label' => 'Sync assets detected through network discovery',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
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
