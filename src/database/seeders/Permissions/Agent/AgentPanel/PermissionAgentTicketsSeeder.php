<?php

namespace Database\Seeders\Permissions\Agent\AgentPanel;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionAgentTicketsSeeder extends Seeder
{
    public function run(): void
    {
        $group = PermissionGroup::updateOrCreate(
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

        $permissions = [
            [
                'key' => 'agent.tickets.create_a_ticket',
                'label' => 'Create a Ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 10,
            ],
            [
                'key' => 'agent.add_ons.respond_a_ticket',
                'label' => 'Respond a Ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 20,
            ],
            [
                'key' => 'agent.add_ons.reply_a_ticket',
                'label' => 'Reply a Ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.add_ons.respond_a_ticket',
                'sort_order' => 30,
            ],
            [
                'key' => 'agent.add_ons.add_an_internal_notes',
                'label' => 'Add an Internal Notes',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'parent_key' => 'agent.add_ons.respond_a_ticket',
                'sort_order' => 40,
            ],
            [
                'key' => 'agent.tickets.forward_a_ticket',
                'label' => 'Forward a Ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 50,
            ],
            [
                'key' => 'agent.tickets.apply_approval_workflow',
                'label' => 'Apply Approval Workflow',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 60,
            ],
            [
                'key' => 'agent.tickets.edit_internal_notes',
                'label' => 'Edit Internal Notes',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 70,
            ],
            [
                'key' => 'agent.tickets.edit_everyone_internal_notes',
                'label' => 'Edit everyone Internal Notes',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.edit_internal_notes',
                'sort_order' => 80,
            ],
            [
                'key' => 'agent.tickets.edit_own_internal_notes',
                'label' => 'Edit own Internal Notes',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.edit_internal_notes',
                'sort_order' => 90,
            ],
            [
                'key' => 'agent.tickets.delete_internal_notes',
                'label' => 'Delete Internal Notes',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 100,
            ],
            [
                'key' => 'agent.tickets.delete_everyone_internal_notes',
                'label' => 'Delete everyone Internal Notes',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.delete_internal_notes',
                'sort_order' => 110,
            ],
            [
                'key' => 'agent.tickets.delete_own_internal_notes',
                'label' => 'Delete own Internal Notes',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.delete_internal_notes',
                'sort_order' => 120,
            ],
            [
                'key' => 'agent.tickets.delete_conversations',
                'label' => 'Delete Conversations',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 130,
            ],
            [
                'key' => 'agent.tickets.delete_everyone_conversations',
                'label' => 'Delete everyone Conversations',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.delete_conversations',
                'sort_order' => 140,
            ],
            [
                'key' => 'agent.tickets.delete_own_conversations',
                'label' => 'Delete own Conversations',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.delete_conversations',
                'sort_order' => 150,
            ],
            [
                'key' => 'agent.tickets.merge_a_ticket',
                'label' => 'Merge a Ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 160,
            ],
            [
                'key' => 'agent.tickets.edit_ticket_properties',
                'label' => 'Edit Ticket Properties',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 170,
            ],
            [
                'key' => 'agent.tickets.edit_all_ticket_properties',
                'label' => 'Edit all Ticket Properties',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.edit_ticket_properties',
                'sort_order' => 180,
            ],
            [
                'key' => 'agent.tickets.edit_specific_ticket_properties',
                'label' => 'Edit specific Ticket Properties',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.edit_ticket_properties',
                'sort_order' => 190,
            ],
            [
                'key' => 'agent.tickets.delete_a_ticket',
                'label' => 'Edit a Ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 200,
            ],
            [
                'key' => 'agent.tickets.export_a_ticket',
                'label' => 'Export a Ticket',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 210,
            ],
            [
                'key' => 'agent.tickets.ticket_visibility',
                'label' => 'Ticket Visibility',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 220,
            ],
            [
                'key' => 'agent.tickets.shows_all_the_tickets',
                'label' => 'Shows all the Tickets',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.ticket_visibility',
                'sort_order' => 230,
            ],
            [
                'key' => 'agent.tickets.shows_the_department_tickets',
                'label' => 'Shows the Department Tickets',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.ticket_visibility',
                'sort_order' => 240,
            ],
            [
                'key' => 'agent.tickets.shows_team_tickets',
                'label' => 'Shows Team Tickets',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.ticket_visibility',
                'sort_order' => 250,
            ],
            [
                'key' => 'agent.tickets.shows_assigned_tickets',
                'label' => 'Shows Assigned Tickets',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.ticket_visibility',
                'sort_order' => 260,
            ],
            [
                'key' => 'agent.tickets.view_approval_pending_tickets',
                'label' => 'View Approval Pending Tickets',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 270,
            ],
            [
                'key' => 'agent.tickets.edit_time_tracks',
                'label' => 'Edit Time Tracks',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 280,
            ],
            [
                'key' => 'agent.tickets.edit_own_time_tracks',
                'label' => 'Edit own Time Tracks',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.edit_time_tracks',
                'sort_order' => 290,
            ],
            [
                'key' => 'agent.tickets.edit_everyone_time_tracks',
                'label' => 'Edit everyone Time Tracks',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.edit_time_tracks',
                'sort_order' => 300,
            ],
            [
                'key' => 'agent.tickets.delete_time_tracks',
                'label' => 'Delete Time Tracks',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 310,
            ],
            [
                'key' => 'agent.tickets.delete_own_time_tracks',
                'label' => 'Delete own Time Tracks',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.delete_time_tracks',
                'sort_order' => 320,
            ],
            [
                'key' => 'agent.tickets.delete_everyone_time_tracks',
                'label' => 'Delete everyone Time Tracks',
                'type' => 'agent',
                'ui_type' => 'radio',
                'parent_key' => 'agent.tickets.delete_time_tracks',
                'sort_order' => 330,
            ],
            [
                'key' => 'agent.tickets.add_or_detach_tickets_in_the_service_desk',
                'label' => 'Add or detach tickets in the Service Desk',
                'type' => 'agent',
                'ui_type' => 'checkbox',
                'sort_order' => 340,
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
