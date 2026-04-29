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
                'label' => 'Manage Products',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'admin.service_desk.manage_procurement_modes',
                'label' => 'Manage Procurement Modes',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'admin.service_desk.manage_contract_types',
                'label' => 'Manage Contract Types',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 30,
            ],
            [
                'key' => 'admin.service_desk.manage_software_license_types',
                'label' => 'Manage Software License Types',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 40,
            ],
            [
                'key' => 'admin.service_desk.manage_vendors',
                'label' => 'Manage Vendors',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 50,
            ],
            [
                'key' => 'admin.service_desk.manage_asset_types',
                'label' => 'Manage Asset Types',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 60,
            ],
            [
                'key' => 'admin.service_desk.manage_change_types',
                'label' => 'Manage Change Types',
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
                'key' => 'admin.service_desk.manage_announcement',
                'label' => 'Manage Announcement',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 90,
            ],
            [
                'key' => 'admin.service_desk.manage_qr_code',
                'label' => 'Manage QR Code',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 100,
            ],
            [
                'key' => 'admin.service_desk.manage_asset_import',
                'label' => 'Manage Asset Import',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 110,
            ],
            [
                'key' => 'admin.service_desk.manage_cmdb_relations',
                'label' => 'Manage CMDB Relations',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 120,
            ],
            [
                'key' => 'admin.service_desk.manage_status',
                'label' => 'Manage Status',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 130,
            ],
            [
                'key' => 'admin.service_desk.manage_depreciation',
                'label' => 'Manage Depreciation',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 140,
            ],
            [
                'key' => 'admin.service_desk.manage_manufacturer',
                'label' => 'Manage Manufacturer',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 150,
            ],
            [
                'key' => 'admin.service_desk.manage_currency',
                'label' => 'Manage Currency',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 160,
            ],
            [
                'key' => 'admin.service_desk.manage_network_discovery',
                'label' => 'Manage Network Discovery',
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
