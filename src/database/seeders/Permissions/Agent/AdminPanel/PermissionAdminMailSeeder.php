<?php

namespace Database\Seeders\Permissions\Agent\AdminPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionAdminMailSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'mail',
                'panel' => 'admin',
                'type' => 'agent',
            ],
            [
                'label' => 'Email',
                'sort_order' => 55,
            ]
        );

        $permissions = [
            [
                'key' => 'admin.mail.view',
                'label' => 'View email settings',
                'sort_order' => 10,
            ],
            [
                'key' => 'admin.mail.manage_mailboxes',
                'label' => 'Manage mailboxes',
                'sort_order' => 20,
            ],
            [
                'key' => 'admin.mail.manage_channels',
                'label' => 'Manage mailbox channels',
                'sort_order' => 30,
            ],
            [
                'key' => 'admin.mail.manage_provider_connections',
                'label' => 'Manage mail provider connections',
                'sort_order' => 40,
            ],
            [
                'key' => 'admin.mail.test_connections',
                'label' => 'Test email connections',
                'sort_order' => 50,
            ],
            [
                'key' => 'admin.mail.view_diagnostics',
                'label' => 'View email diagnostics',
                'sort_order' => 60,
            ],
            [
                'key' => 'admin.mail.manage_quarantine',
                'label' => 'Manage email quarantine',
                'sort_order' => 70,
            ],
            [
                'key' => 'admin.mail.sync_mailboxes',
                'label' => 'Synchronize mailboxes manually',
                'sort_order' => 80,
            ],
            [
                'key' => 'admin.mail.retry_messages',
                'label' => 'Retry outgoing email messages',
                'sort_order' => 90,
            ],
            [
                'key' => 'admin.mail.rescan_attachments',
                'label' => 'Rescan email attachments',
                'sort_order' => 100,
            ],
        ];

        $permissionIds = [];

        foreach ($permissions as $permission) {
            $model = Permission::updateOrCreate(
                [
                    'key' => $permission['key'],
                ],
                [
                    'permission_group_id' => $group->id,
                    'parent_id' => null,
                    'label' => $permission['label'],
                    'type' => 'agent',
                    'ui_type' => 'checkbox',
                    'description' => null,
                    'sort_order' => $permission['sort_order'],
                ]
            );

            $permissionIds[] = $model->id;
        }

        Role::query()
            ->whereIn('name', [
                'super_admin',
                'admin',
            ])
            ->get()
            ->each(
                static fn (Role $role) => $role
                    ->permissions()
                    ->syncWithoutDetaching($permissionIds)
            );
    }
}
