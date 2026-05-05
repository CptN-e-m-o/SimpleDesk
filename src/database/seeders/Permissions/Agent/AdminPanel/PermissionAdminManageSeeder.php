<?php

namespace Database\Seeders\Permissions\Agent\AdminPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAdminManageSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'manage',
                'panel' => 'admin',
                'type' => 'agent',
            ],
            [
                'label' => 'Manage',
                'sort_order' => 30,
            ]
        );

        $permissions = [
            [
                'key' => 'admin.manage.manage_automator',
                'label' => 'Manage Automator',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'admin.manage.manage_help_topics',
                'label' => 'Manage Help Topics',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'admin.manage.manage_sla_plans',
                'label' => 'Manage SLA Plans',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 30,
            ],
            [
                'key' => 'admin.manage.manage_business_hours',
                'label' => 'Manage Business Hours',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 40,
            ],
            [
                'key' => 'admin.manage.manage_forms',
                'label' => 'Manage Forms',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 50,
            ],
            [
                'key' => 'admin.manage.manage_ticket_fields',
                'label' => 'Manage Ticket Fields',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 60,
            ],
            [
                'key' => 'admin.manage.manage_approval_workflow',
                'label' => 'Manage Approval Workflow',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 70,
            ],
            [
                'key' => 'admin.manage.manage_priority',
                'label' => 'Manage Priority',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 80,
            ],
            [
                'key' => 'admin.manage.manage_ticket_types',
                'label' => 'Manage Ticket Types',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 90,
            ],
            [
                'key' => 'admin.manage.manage_widgets',
                'label' => 'Manage Widgets',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 100,
            ],
            [
                'key' => 'admin.manage.manage_daily_report',
                'label' => 'Manage Daily Report',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 110,
            ],
            [
                'key' => 'admin.manage.manage_dashboard',
                'label' => 'Manage Dashboard',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 120,
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
