<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use App\Models\Permission;
use App\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Agent roles
        |--------------------------------------------------------------------------
        */

        $this->syncByQuery(
            'super-admin',
            Permission::query()->where('type', 'agent')
        );

        $this->syncByQuery('admin', [
            // General
            'admin.general.manage_notify',
            // Manage
            'admin.manage.manage_help_topics',
            'admin.manage.manage_sla_plans',
            'admin.manage.manage_business_hours',
            'admin.manage.manage_forms',
            'admin.manage.manage_ticket_fields',
            'admin.manage.manage_approval_workflow',
            'admin.manage.manage_priority',
            'admin.manage.manage_ticket_types',
            'admin.manage.manage_widgets',
            'admin.manage.manage_daily_report',
            'admin.manage.manage_dashboard',
            // Settings
            'admin.settings.manage_company',
            'admin.settings.manage_contact_options',
            'admin.settings.manage_languages',
            'admin.settings.manage_social_widget_settings',
            'admin.settings.manage_client_panel_filter',
            // Service Desk
            'admin.service_desk.manage_products',
            'admin.service_desk.manage_procurement_modes',
            'admin.service_desk.manage_contract_types',
            'admin.service_desk.manage_software_license_types',
            'admin.service_desk.manage_vendors',
            'admin.service_desk.manage_asset_types',
            'admin.service_desk.manage_change_types',
            'admin.service_desk.manage_cab',
            'admin.service_desk.manage_announcements',
            'admin.service_desk.manage_qr_codes',
            'admin.service_desk.manage_cmdb_relations',
            'admin.service_desk.manage_statuses',
            'admin.service_desk.manage_manufacturers',
            // Staff
            'admin.staff.manage_agents',
            'admin.staff.manage_teams',
            'admin.staff.manage_departments',
            // Tickets
            'admin.tickets.manage_ticket_settings',
            'admin.tickets.manage_status',
            'admin.tickets.manage_labels',
            'admin.tickets.manage_ratings',
            'admin.tickets.manage_close_ticket_workflow',
            'admin.tickets.manage_tags',
            'admin.tickets.manage_auto_assign',
            'admin.tickets.manage_source',
            'admin.tickets.manage_recurring',
            'admin.tickets.manage_location',
            'admin.tickets.manage_template',
            'admin.tickets.manage_pdf_template',
            // Add-ons
            'admin.add_ons.manage_modules',
            'admin.add_ons.manage_modules.timetrack',
            // Assets
            'agent.assets.create',
            'agent.assets.view',
            'agent.assets.edit',
            'agent.assets.delete',
            'agent.assets.attach_or_detach',
            // Changes
            'agent.changes.create',
            'agent.changes.view',
            'agent.changes.edit',
            'agent.changes.edit_while_cab_applied',
            'agent.changes.edit_assignee',
            'agent.changes.edit_status',
            'agent.changes.update_activity_duration',
            'agent.changes.delete',
            'agent.changes.attach_or_detach_plannings',
            'agent.changes.apply_or_remove_cab',
            'agent.changes.discussions.actions',
            'agent.changes.discussions.add_or_edit_comment',
            'agent.changes.discussions.delete_comment',
            'agent.changes.attach_or_detach',
            // Contacts
            'agent.contacts.view',
            'agent.contacts.create_or_edit',
            'agent.contacts.actions',
            'agent.contacts.change_role',
            'agent.contacts.switch_to_user_roles',
            'agent.contacts.switch_to_agent_roles',
            'agent.contacts.change_password',
            'agent.contacts.change_user_password',
            'agent.contacts.change_agent_password',
            'agent.contacts.deactivate_account',
            'agent.contacts.deactivate_user_account',
            'agent.contacts.deactivate_agent_account',
            'agent.contacts.restore_account',
            'agent.contacts.restore_user_account',
            'agent.contacts.restore_agent_account',
            'agent.contacts.disable_2fa',
            'agent.contacts.export',
            'agent.contacts.merge',
            'agent.organizations.view',
            'agent.organizations.create_or_edit',
            'agent.organizations.actions',
            'agent.organizations.add_manager',
            'agent.organizations.link_or_unlink_contacts',
            'agent.organizations.delete',
            // Contracts
            'agent.contracts.create',
            'agent.contracts.create_type',
            'agent.contracts.create_vendor',
            'agent.contracts.view',
            'agent.contracts.edit',
            'agent.contracts.terminate',
            'agent.contracts.extend_or_renew',
            'agent.contracts.set_expiry_reminder',
            'agent.contracts.attach_or_detach',
            // Problems
            'agent.problems.create',
            'agent.problems.view',
            'agent.problems.edit',
            'agent.problems.close',
            'agent.problems.attach_or_detach_plannings',
            'agent.problems.attach_or_detach',
            // Releases
            'agent.releases.create',
            'agent.releases.view',
            'agent.releases.edit',
            'agent.releases.mark_complete',
            'agent.releases.attach_or_detach_plannings',
            'agent.releases.attach_or_detach',
            // Reports
            'agent.reports.analytics',
            'agent.reports.helpdesk',
            'agent.reports.service_desk',
            'agent.reports.settings',
            // Software licenses
            'agent.software_licenses.create',
            'agent.software_licenses.view',
            'agent.software_licenses.edit',
            'agent.software_licenses.check_in_out',
            // Tickets
            'agent.tickets.create',
            'agent.tickets.respond',
            'agent.tickets.reply',
            'agent.tickets.add_internal_notes',
            'agent.tickets.forward',
            'agent.tickets.apply_approval_workflow',
            'agent.tickets.edit_internal_notes',
            'agent.tickets.edit_everyone_internal_notes',
            'agent.tickets.delete_internal_notes',
            'agent.tickets.delete_everyone_internal_notes',
            'agent.tickets.delete_conversations',
            'agent.tickets.delete_everyone_conversations',
            'agent.tickets.merge',
            'agent.tickets.edit_properties',
            'agent.tickets.edit_all_properties',
            'agent.tickets.export',
            'agent.tickets.visibility',
            'agent.tickets.visibility.department',
            'agent.tickets.view_approval_pending',
            'agent.tickets.edit_time_tracks',
            'agent.tickets.edit_everyone_time_tracks',
            'agent.tickets.delete_time_tracks',
            'agent.tickets.delete_everyone_time_tracks',
            'agent.tickets.attach_service_desk',
            // Tools
            'agent.tools.canned_responses',
            'agent.tools.access_all_canned_responses',
            'agent.tools.create_edit_canned_responses',
            'agent.tools.delete_canned_responses',
            'agent.tools.knowledge_base',
            'agent.tools.categories',
            'agent.tools.add_or_edit_categories',
            'agent.tools.delete_categories',
            'agent.tools.articles',
            'agent.tools.access_all_articles',
            'agent.tools.add_or_edit_articles',
            'agent.tools.publish_article',
            'agent.tools.delete_article',
            'agent.tools.templates',
            'agent.tools.add_edit_template',
            'agent.tools.delete_templates',
            'agent.tools.pages',
            'agent.tools.add_or_edit_pages',
            'agent.tools.publish_page',
            'agent.tools.delete_pages',
            'agent.tools.comments',
            'agent.tools.approve_unapprove_comments',
            'agent.tools.delete_comments',
            'agent.tools.recurring_tickets',
            'agent.tools.add_edit_recurring_tickets',
            'agent.tools.delete_recurring_tickets',
            // Billing
            'agent.billing.owned_package',
            // Client tickets
            'agent.client.tickets.create',
            'agent.client.tickets.respond',
            'agent.client.tickets.change_status',
            'agent.client.tickets.visibility',
            'agent.client.tickets.visibility.requester',
            'agent.client.tickets.collaborator.view',
            // General - Notifications
            'agent.general.notifications.enabled',
            'agent.general.notifications.receive_admin_notifications',
            // General - Visibility
            'agent.general.visibility.show_health_alert_icon',
            'agent.general.visibility.display_all_labels',
        ]);

        $this->syncByKeys('agent', [
            //Tickets
            'agent.tickets.create',
            'agent.tickets.respond',
            'agent.tickets.reply',
            'agent.tickets.add_internal_notes',
            'agent.tickets.forward',
            'agent.tickets.edit_internal_notes',
            'agent.tickets.edit_own_internal_notes',
            'agent.tickets.delete_internal_notes',
            'agent.tickets.delete_own_internal_notes',
            'agent.tickets.delete_conversations',
            'agent.tickets.delete_own_conversations',
            'agent.tickets.merge',
            'agent.tickets.edit_properties',
            'agent.tickets.edit_all_properties',
            'agent.tickets.delete',
            'agent.tickets.export',
            'agent.tickets.visibility',
            'agent.tickets.visibility.assigned',
            'agent.tickets.view_approval_pending',
            'agent.tickets.edit_time_tracks',
            'agent.tickets.edit_own_time_tracks',
            'agent.tickets.delete_time_tracks',
            'agent.tickets.delete_own_time_tracks',
            //Tools
            'agent.tools.canned_responses',
            'agent.tools.access_all_canned_responses',
            'agent.tools.articles',
            'agent.tools.access_all_articles',
            //Problems
            'agent.problems.create',
            'agent.problems.view',
            'agent.problems.edit',
            'agent.problems.attach_or_detach_plannings',
            // Contacts
            'agent.contacts.view',
            'agent.contacts.create_or_edit',
            'agent.organizations.view',
            'agent.organizations.create_or_edit',
            'agent.organizations.link_or_unlink_contacts',
            //Reports
            'agent.reports.analytics',
            'agent.reports.helpdesk',
            // Changes
            'agent.changes.create',
            'agent.changes.view',
            'agent.changes.edit',
            'agent.changes.update_activity_duration',
            'agent.changes.attach_or_detach',
            'agent.changes.discussions.actions',
            'agent.changes.discussions.add_or_edit_comment',
            // Assets
            'agent.assets.create',
            'agent.assets.view',
            'agent.assets.edit',
            'agent.assets.attach_or_detach',
            // Software licenses
            'agent.software_licenses.view',
            'agent.software_licenses.check_in_out',
            // Client tickets (agent as requester)
            'agent.client.tickets.create',
            'agent.client.tickets.respond',
            'agent.client.tickets.change_status',
            'agent.client.tickets.visibility',
            'agent.client.tickets.visibility.requester',
            'agent.client.tickets.collaborator.view',
            //General Panel
            'agent.general.notifications.enabled',
            'agent.general.notifications.receive_agent_notifications',
        ]);

        /*
        |--------------------------------------------------------------------------
        | User roles
        |--------------------------------------------------------------------------
        */

        $this->syncByKeys('user', [
            'tickets.create',

            'tickets.respond',

            'tickets.visibility',
            'tickets.visibility.requester',
        ]);

        $this->syncByKeys('organization-user', [
            'tickets.create',
            'tickets.create_with_organization_assets',

            'tickets.respond',

            'tickets.visibility',
            'tickets.visibility.organization',
        ]);

        $this->syncByKeys('collaborators', [
            'tickets.collaborator.view',
        ]);

        $this->syncByKeys('agent-collaborators', [
            'agent.tickets.visibility',
            'agent.tickets.visibility.assigned',
            'agent.client.tickets.collaborator.view',
            'agent.client.tickets.visibility',
            'agent.client.tickets.visibility.requester',
        ]);
    }

    private function syncByKeys(string $roleName, array $permissionKeys): void
    {
        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            return;
        }

        $permissionIds = Permission::whereIn('key', $permissionKeys)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
    }

    private function syncByQuery(string $roleName, $query): void
    {
        $role = Role::where('name', $roleName)->first();

        if (! $role) {
            return;
        }

        $permissionIds = $query
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
    }
}
