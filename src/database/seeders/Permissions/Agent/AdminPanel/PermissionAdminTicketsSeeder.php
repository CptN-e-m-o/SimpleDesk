<?php

namespace Database\Seeders\Permissions\Agent\AdminPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAdminTicketsSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
            [
                'key' => 'tickets',
                'panel' => 'admin',
                'type' => 'agent',
            ],
            [
                'label' => 'Tickets',
                'sort_order' => 40,
            ]
        );

        $permissions = [
            [
                'key' => 'admin.tickets.manage_ticket_settings',
                'label' => 'Manage ticket settings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'admin.tickets.manage_status',
                'label' => 'Manage status',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'admin.tickets.manage_labels',
                'label' => 'Manage labels',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 30,
            ],
            [
                'key' => 'admin.tickets.manage_ratings',
                'label' => 'Manage ratings',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 40,
            ],
            [
                'key' => 'admin.tickets.manage_close_ticket_workflow',
                'label' => 'Manage close ticket workflow',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 50,
            ],
            [
                'key' => 'admin.tickets.manage_tags',
                'label' => 'Manage tags',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 60,
            ],
            [
                'key' => 'admin.tickets.manage_auto_assign',
                'label' => 'Manage auto assign',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 70,
            ],
            [
                'key' => 'admin.tickets.manage_source',
                'label' => 'Manage source',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 80,
            ],
            [
                'key' => 'admin.tickets.manage_recurring',
                'label' => 'Manage recurring',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 90,
            ],
            [
                'key' => 'admin.tickets.manage_location',
                'label' => 'Manage location',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 100,
            ],
            [
                'key' => 'admin.tickets.manage_template',
                'label' => 'Manage template',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 110,
            ],
            [
                'key' => 'admin.tickets.manage_pdf_template',
                'label' => 'Manage PDF template',
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
