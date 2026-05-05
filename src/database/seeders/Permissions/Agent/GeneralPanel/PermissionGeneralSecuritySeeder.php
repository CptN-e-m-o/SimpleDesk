<?php

namespace Database\Seeders\Permissions\Agent\GeneralPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionGeneralSecuritySeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'security',
                'panel' => 'general',
                'type' => 'agent',
            ],
            [
                'label' => 'Security',
                'sort_order' => 20,
            ]
        );

        $permissions = [
            [
                'key' => 'agent.general.security.allow_login_when_offline',
                'label' => 'Allow login even when the system is offline',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
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
