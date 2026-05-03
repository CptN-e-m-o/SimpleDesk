<?php

namespace Database\Seeders\Permissions\Agent\AdminPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAdminServiceDeskSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'service_desk',
                'panel' => 'admin',
                'type' => 'agent',
            ],
            [
                'label' => 'Service Desk',
                'sort_order' => 70,
            ]
        );

        $permissions = [
            [
                'key' => 'admin.service_desk.manage_products',
                'label' => 'Manage products',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'admin.service_desk.manage_procurement_modes',
                'label' => 'Manage procurement modes',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'admin.service_desk.manage_contract_types',
                'label' => 'Manage contract types',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 30,
            ],
            [
                'key' => 'admin.service_desk.manage_software_license_types',
                'label' => 'Manage software license types',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 40,
            ],
            [
                'key' => 'admin.service_desk.manage_vendors',
                'label' => 'Manage vendors',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 50,
            ],
            [
                'key' => 'admin.service_desk.manage_asset_types',
                'label' => 'Manage asset types',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 60,
            ],
            [
                'key' => 'admin.service_desk.manage_change_types',
                'label' => 'Manage change types',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 70,
            ],
            [
                'key' => 'admin.service_desk.manage_cab',
                'label' => 'Manage CAB',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 80,
            ],
            [
                'key' => 'admin.service_desk.manage_announcements',
                'label' => 'Manage announcements',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 90,
            ],
            [
                'key' => 'admin.service_desk.manage_qr_codes',
                'label' => 'Manage QR codes',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 100,
            ],
            [
                'key' => 'admin.service_desk.manage_asset_import',
                'label' => 'Manage asset import',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 110,
            ],
            [
                'key' => 'admin.service_desk.manage_cmdb_relations',
                'label' => 'Manage CMDB relations',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 120,
            ],
            [
                'key' => 'admin.service_desk.manage_statuses',
                'label' => 'Manage statuses',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 130,
            ],
            [
                'key' => 'admin.service_desk.manage_depreciation',
                'label' => 'Manage depreciation',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 140,
            ],
            [
                'key' => 'admin.service_desk.manage_manufacturers',
                'label' => 'Manage manufacturers',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 150,
            ],
            [
                'key' => 'admin.service_desk.manage_currencies',
                'label' => 'Manage currencies',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 160,
            ],
            [
                'key' => 'admin.service_desk.manage_network_discovery',
                'label' => 'Manage network discovery',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 170,
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
