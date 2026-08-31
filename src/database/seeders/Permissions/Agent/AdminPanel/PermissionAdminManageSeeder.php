<?php

namespace Database\Seeders\Permissions\Agent\AdminPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionAdminManageSeeder extends Seeder
{
    public function run(): void
    {
        $legacyPermissions = Permission::query()
            ->with('roles:id')
            ->whereIn('key', [
                'admin.manage.manage_priority',
                'admin.manage.manage_ticket_types',
            ])
            ->get();

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
            ['key' => 'admin.manage.priorities.view', 'label' => 'View Priorities', 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 81],
            ['key' => 'admin.manage.priorities.create', 'label' => 'Create Priorities', 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 82],
            ['key' => 'admin.manage.priorities.update', 'label' => 'Update Priorities', 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 83],
            ['key' => 'admin.manage.priorities.archive', 'label' => 'Archive Priorities', 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 84],
            ['key' => 'admin.manage.ticket_types.view', 'label' => 'View Ticket Types', 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 91],
            ['key' => 'admin.manage.ticket_types.create', 'label' => 'Create Ticket Types', 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 92],
            ['key' => 'admin.manage.ticket_types.update', 'label' => 'Update Ticket Types', 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 93],
            ['key' => 'admin.manage.ticket_types.archive', 'label' => 'Archive Ticket Types', 'type' => 'agent', 'ui_type' => 'checkbox', 'sort_order' => 94],
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
            Permission::updateOrCreate(
                ['key' => $permission['key']],
                [
                    ...$permission,
                    'permission_group_id' => $group->id,
                    'parent_id' => null,
                ]
            );
        }

        DB::transaction(function () use ($legacyPermissions) {
            $replacementKeys = [
                'admin.manage.manage_priority' => [
                    'admin.manage.priorities.view',
                    'admin.manage.priorities.create',
                    'admin.manage.priorities.update',
                    'admin.manage.priorities.archive',
                ],
                'admin.manage.manage_ticket_types' => [
                    'admin.manage.ticket_types.view',
                    'admin.manage.ticket_types.create',
                    'admin.manage.ticket_types.update',
                    'admin.manage.ticket_types.archive',
                ],
            ];

            foreach ($legacyPermissions as $legacyPermission) {
                $replacementIds = Permission::query()
                    ->whereIn('key', $replacementKeys[$legacyPermission->key])
                    ->pluck('id')
                    ->all();

                foreach (Role::query()->whereKey($legacyPermission->roles->pluck('id'))->get() as $role) {
                    $role->permissions()->syncWithoutDetaching($replacementIds);
                }

                $legacyPermission->delete();
            }
        });
    }
}
