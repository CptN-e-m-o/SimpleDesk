<?php

namespace Database\Seeders\Permissions\Agent\AgentPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAgentTicketsSeeder extends Seeder
{
    public function run(): void
    {
        $group = $this->createOrUpdateGroup();

        foreach ($this->permissions() as $permission) {
            $this->createOrUpdatePermission($group->id, $permission);
        }
    }

    private function createOrUpdateGroup(): PermissionGroup
    {
        return PermissionGroup::updateOrCreate(
            [
                'key' => 'tickets',
                'panel' => 'agent',
                'type' => 'agent',
            ],
            [
                'label' => 'Tickets',
                'sort_order' => 10,
            ]
        );
    }

    private function permissions(): array
    {
        return [
            [
                'key' => 'agent.tickets.create',
                'label' => 'Create ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.tickets.respond',
                'label' => 'Respond to ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.tickets.reply',
                'label' => 'Reply to ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tickets.respond',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.tickets.add_internal_notes',
                'label' => 'Add internal notes',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.tickets.respond',
                'sort_order' => 40,
            ],
            [
                'key' => 'agent.tickets.forward',
                'label' => 'Forward ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 50,
            ],
            [
                'key' => 'agent.tickets.apply_approval_workflow',
                'label' => 'Apply approval workflow',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 60,
            ],
            [
                'key' => 'agent.tickets.edit_internal_notes',
                'label' => 'Edit internal notes',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 70,
            ],
            [
                'key' => 'agent.tickets.edit_everyone_internal_notes',
                'label' => 'Edit everyone internal notes',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.edit_internal_notes',
                'sort_order' => 80,
            ],
            [
                'key' => 'agent.tickets.edit_own_internal_notes',
                'label' => 'Edit own internal notes',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.edit_internal_notes',
                'sort_order' => 90,
            ],
            [
                'key' => 'agent.tickets.delete_internal_notes',
                'label' => 'Delete internal notes',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 100,
            ],
            [
                'key' => 'agent.tickets.delete_everyone_internal_notes',
                'label' => 'Delete everyone internal notes',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.delete_internal_notes',
                'sort_order' => 110,
            ],
            [
                'key' => 'agent.tickets.delete_own_internal_notes',
                'label' => 'Delete own internal notes',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.delete_internal_notes',
                'sort_order' => 120,
            ],
            [
                'key' => 'agent.tickets.delete_conversations',
                'label' => 'Delete conversations',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 130,
            ],
            [
                'key' => 'agent.tickets.delete_everyone_conversations',
                'label' => 'Delete everyone conversation',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.delete_conversations',
                'sort_order' => 140,
            ],
            [
                'key' => 'agent.tickets.delete_own_conversations',
                'label' => 'Delete own conversation',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.delete_conversations',
                'sort_order' => 150,
            ],
            [
                'key' => 'agent.tickets.merge',
                'label' => 'Merge ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 160,
            ],
            [
                'key' => 'agent.tickets.edit_properties',
                'label' => 'Edit ticket properties',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 170,
            ],
            [
                'key' => 'agent.tickets.edit_all_properties',
                'label' => 'Edit all ticket properties',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.edit_properties',
                'sort_order' => 180,
            ],
            [
                'key' => 'agent.tickets.edit_specific_properties',
                'label' => 'Edit specific ticket properties',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.edit_properties',
                'sort_order' => 190,
            ],
            [
                'key' => 'agent.tickets.delete',
                'label' => 'Delete ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 200,
            ],
            [
                'key' => 'agent.tickets.export',
                'label' => 'Export ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 210,
            ],
            [
                'key' => 'agent.tickets.visibility',
                'label' => 'Tickets visibility',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 220,
            ],
            [
                'key' => 'agent.tickets.visibility.all',
                'label' => 'Show all tickets',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.visibility',
                'sort_order' => 230,
            ],
            [
                'key' => 'agent.tickets.visibility.department',
                'label' => 'Show department tickets',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.visibility',
                'sort_order' => 240,
            ],
            [
                'key' => 'agent.tickets.visibility.team',
                'label' => 'Show team tickets',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.visibility',
                'sort_order' => 250,
            ],
            [
                'key' => 'agent.tickets.visibility.assigned',
                'label' => 'Show assigned tickets',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.visibility',
                'sort_order' => 260,
            ],
            [
                'key' => 'agent.tickets.view_approval_pending',
                'label' => 'View approval pending tickets',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 270,
            ],
            [
                'key' => 'agent.tickets.edit_time_tracks',
                'label' => 'Edit time tracks',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 280,
            ],
            [
                'key' => 'agent.tickets.edit_own_time_tracks',
                'label' => 'Edit own time tracks',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.edit_time_tracks',
                'sort_order' => 290,
            ],
            [
                'key' => 'agent.tickets.edit_everyone_time_tracks',
                'label' => 'Edit everyone time tracks',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.edit_time_tracks',
                'sort_order' => 300,
            ],
            [
                'key' => 'agent.tickets.delete_time_tracks',
                'label' => 'Delete time tracks',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 310,
            ],
            [
                'key' => 'agent.tickets.delete_own_time_tracks',
                'label' => 'Delete own time tracks',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.delete_time_tracks',
                'sort_order' => 320,
            ],
            [
                'key' => 'agent.tickets.delete_everyone_time_tracks',
                'label' => 'Delete everyone time tracks',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.delete_time_tracks',
                'sort_order' => 330,
            ],
            [
                'key' => 'agent.tickets.attach_service_desk',
                'label' => 'Add or detach tickets in Service Desk',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 340,
            ],

        ];
    }

    private function createOrUpdatePermission(int $groupId, array $permission): void
    {
        $parentKey = $permission['parent_key'] ?? null;

        unset($permission['parent_key']);

        Permission::updateOrCreate(
            ['key' => $permission['key']],
            [
                ...$permission,
                'permission_group_id' => $groupId,
                'parent_id' => $this->resolveParentId($parentKey),
            ]
        );
    }

    private function resolveParentId(?string $parentKey): ?int
    {
        if (! $parentKey) {
            return null;
        }

        return Permission::where('key', $parentKey)->value('id');
    }
}
