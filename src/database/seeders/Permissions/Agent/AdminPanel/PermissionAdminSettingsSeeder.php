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
            ]
        );

        $permissions = [
            [
                'key' => 'admin.settings.manage_company',
                'label' => 'Manage Company',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'admin.settings.manage_system',
                'label' => 'Manage System',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'admin.settings.manage_contact_options',
                'label' => 'Manage Contact Options',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 30,
            ],
            [
                'key' => 'admin.settings.manage_social_login',
                'label' => 'Manage Social Login',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 40,
            ],
            [
                'key' => 'admin.settings.manage_languages',
                'label' => 'Manage Languages',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 50,
            ],
            [
                'key' => 'admin.settings.manage_cron_scheduling',
                'label' => 'Manage Cron Scheduling',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 60,
            ],
            [
                'key' => 'admin.settings.manage_security',
                'label' => 'Manage Security',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 70,
            ],
            [
                'key' => 'admin.settings.manage_file_system',
                'label' => 'Manage File System',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 80,
            ],
            [
                'key' => 'admin.settings.manage_system_backup',
                'label' => 'Manage System Backup',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 90,
            ],
            [
                'key' => 'admin.settings.manage_social_widget_settings',
                'label' => 'Manage Social Widget Settings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 100,
            ],
            [
                'key' => 'admin.settings.manage_webhooks',
                'label' => 'Manage Webhooks',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 110,
            ],
            [
                'key' => 'admin.settings.manage_user_import',
                'label' => 'Manage User Import',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 120,
            ],
            [
                'key' => 'admin.settings.manage_recaptcha',
                'label' => 'Manage Recaptcha',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 130,
            ],
            [
                'key' => 'admin.settings.manage_login_log_setiings',
                'label' => 'Manage Login Log Settings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 140,
            ],
            [
                'key' => 'admin.settings.manage_client_panel_filter',
                'label' => 'Manage Client Panel Filter',
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
