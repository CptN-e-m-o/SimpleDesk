<?php

namespace Database\Seeders\Permissions\Agent\AdminPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAdminSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'settings',
                'panel' => 'admin',
                'type' => 'agent',
            ],
            [
                'label' => 'Settings',
                'sort_order' => 50,
            ],
        );

        $permissions = [
            [
                'key' => 'admin.settings.manage_company',
                'label' => 'Manage company settings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'admin.settings.manage_system',
                'label' => 'Manage system settings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'admin.settings.manage_contact_options',
                'label' => 'Manage contact options',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 30,
            ],
            [
                'key' => 'admin.settings.drivers.view',
                'label' => 'View system drivers',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 21,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.drivers.manage',
                'label' => 'Manage system drivers',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 22,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.infrastructure_connections.view',
                'label' => 'View infrastructure connections',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 23,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.infrastructure_connections.create',
                'label' => 'Create infrastructure connections',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 24,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.infrastructure_connections.update',
                'label' => 'Update infrastructure connections',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 25,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.infrastructure_connections.archive',
                'label' => 'Archive infrastructure connections',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 26,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.infrastructure_connections.delete',
                'label' => 'Permanently delete infrastructure connections',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 27,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.infrastructure_connections.test',
                'label' => 'Test infrastructure connections',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 28,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.system_audit.view',
                'label' => 'View system audit',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 29,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.queues.view',
                'label' => 'View queue configurations',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 30,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.queues.create',
                'label' => 'Create queue configurations',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 31,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.queues.update',
                'label' => 'Update queue configurations',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 32,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.queues.archive',
                'label' => 'Archive queue configurations',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 33,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.queues.delete',
                'label' => 'Permanently delete queue configurations',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 34,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.queues.test',
                'label' => 'Test queue configurations',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 35,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.queues.activate',
                'label' => 'Activate queue configurations',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 36,
                'parent_key' => 'admin.settings.manage_system',
            ],
            [
                'key' => 'admin.settings.queues.force_activate',
                'label' => 'Force activate queue configurations',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 37,
                'parent_key' => 'admin.settings.manage_system',
            ],
            ...array_map(fn (array $permission): array => [
                'key' => 'admin.settings.cache.'.$permission[0],
                'label' => $permission[1],
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => $permission[2],
                'parent_key' => 'admin.settings.manage_system',
            ], [
                ['view', 'View cache configurations', 38],
                ['create', 'Create cache configurations', 39],
                ['update', 'Update cache configurations', 40],
                ['archive', 'Archive cache configurations', 41],
                ['delete', 'Permanently delete cache configurations', 42],
                ['test', 'Test cache configurations', 43],
                ['activate', 'Activate cache configurations', 44],
                ['force_activate', 'Force activate cache configurations', 45],
            ]),
            ...array_map(fn (array $permission): array => [
                'key' => 'admin.settings.broadcasting.'.$permission[0],
                'label' => $permission[1],
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => $permission[2],
                'parent_key' => 'admin.settings.manage_system',
            ], [
                ['view', 'View real-time configurations', 46],
                ['create', 'Create real-time configurations', 47],
                ['update', 'Update real-time configurations', 48],
                ['archive', 'Archive real-time configurations', 49],
                ['delete', 'Permanently delete real-time configurations', 50],
                ['test', 'Test real-time configurations', 51],
                ['activate', 'Activate real-time configurations', 52],
                ['force_activate', 'Force activate real-time configurations', 53],
            ]),
            ...array_map(fn (array $permission): array => [
                'key' => 'admin.settings.search.'.$permission[0],
                'label' => $permission[1],
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => $permission[2],
                'parent_key' => 'admin.settings.manage_system',
            ], [
                ['view', 'View Search configurations', 54],
                ['create', 'Create Search configurations', 55],
                ['update', 'Update Search configurations', 56],
                ['archive', 'Archive Search configurations', 57],
                ['delete', 'Permanently delete Search configurations', 58],
                ['test', 'Test Search configurations', 59],
                ['activate', 'Activate Search configurations', 60],
                ['force_activate', 'Force activate Search configurations', 61],
            ]),
            ...array_map(fn (array $permission): array => [
                'key' => 'admin.settings.storage.'.$permission[0],
                'label' => $permission[1],
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => $permission[2],
                'parent_key' => 'admin.settings.manage_system',
            ], [
                ['view', 'View Storage configurations', 62],
                ['create', 'Create Storage configurations', 63],
                ['update', 'Update Storage configurations', 64],
                ['archive', 'Archive Storage configurations', 65],
                ['delete', 'Permanently delete Storage configurations', 66],
                ['test', 'Test Storage configurations', 67],
                ['activate', 'Activate Storage configurations', 68],
                ['force_activate', 'Force activate Storage configurations', 69],
            ]),
            [
                'key' => 'admin.settings.manage_social_login',
                'label' => 'Manage social login',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 40,
            ],
            [
                'key' => 'admin.settings.manage_languages',
                'label' => 'Manage languages',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 50,
            ],
            [
                'key' => 'admin.settings.manage_cron_scheduling',
                'label' => 'Manage cron scheduling',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 60,
            ],
            [
                'key' => 'admin.settings.manage_security',
                'label' => 'Manage security settings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 70,
            ],
            [
                'key' => 'admin.settings.manage_file_system',
                'label' => 'Manage file system',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 80,
            ],
            [
                'key' => 'admin.settings.manage_system_backup',
                'label' => 'Manage system backups',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 90,
            ],
            [
                'key' => 'admin.settings.manage_social_widget_settings',
                'label' => 'Manage social widget settings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 100,
            ],
            [
                'key' => 'admin.settings.manage_webhooks',
                'label' => 'Manage webhooks',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 110,
            ],
            [
                'key' => 'admin.settings.manage_user_import',
                'label' => 'Manage user import',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 120,
            ],
            [
                'key' => 'admin.settings.manage_recaptcha',
                'label' => 'Manage reCAPTCHA',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 130,
            ],
            [
                'key' => 'admin.settings.manage_login_log_settings',
                'label' => 'Manage login log settings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 140,
            ],
            [
                'key' => 'admin.settings.manage_client_panel_filter',
                'label' => 'Manage client panel filter',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 150,
            ],
        ];

        foreach ($permissions as $permission) {
            $parentKey = $permission['parent_key'] ?? null;

            unset($permission['parent_key']);

            $parentId = null;

            if ($parentKey) {
                $parentId = Permission::where(
                    'key',
                    $parentKey,
                )->value('id');
            }

            Permission::updateOrCreate(
                [
                    'key' => $permission['key'],
                ],
                [
                    ...$permission,
                    'permission_group_id' => $group->id,
                    'parent_id' => $parentId,
                ],
            );
        }
    }
}
