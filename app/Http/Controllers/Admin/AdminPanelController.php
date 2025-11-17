<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\Factory;
use Illuminate\View\View;

class AdminPanelController extends Controller
{
    public function index(): Factory|View
    {
        $sections = [
            __('lang.sections.users') => [
                ['icon' => 'bi-person-fill', 'label' => __('lang.users.agents'), 'route' => 'admin.agents.index'],
                ['icon' => 'bi-person-check-fill', 'label' => __('lang.users.roles')],
                ['icon' => 'bi-diagram-3-fill', 'label' => __('lang.users.departments')],
                ['icon' => 'bi-people-fill', 'label' => __('lang.users.teams')],
            ],
            __('lang.sections.email') => [
                ['icon' => 'bi-envelope-fill', 'label' => __('lang.email.settings')],
                ['icon' => 'bi-slash-circle-fill', 'label' => __('lang.email.breaklines')],
                ['icon' => 'bi-heart-pulse-fill', 'label' => __('lang.email.diagnostics')],
                ['icon' => 'bi-fingerprint', 'label' => __('lang.email.oauth')],
            ],
            __('lang.sections.drivers') => [
                ['icon' => 'bi-arrow-repeat', 'label' => __('lang.drivers.queues')],
                ['icon' => 'bi-cloud-fill', 'label' => __('lang.drivers.cache')],
                ['icon' => 'bi-lightning-charge-fill', 'label' => __('lang.drivers.websockets')],
                ['icon' => 'bi-search', 'label' => __('lang.drivers.search')],
            ],
            __('lang.sections.manage') => [
                ['icon' => 'bi-repeat', 'label' => __('lang.manage.automation')],
                ['icon' => 'bi-file-earmark-text-fill', 'label' => __('lang.manage.help_topics')],
                ['icon' => 'bi-clock', 'label' => __('lang.manage.sla_plans')],
                ['icon' => 'bi-calendar-date', 'label' => __('lang.manage.business_hours')],
                ['icon' => 'bi-file-earmark', 'label' => __('lang.manage.forms')],
                ['icon' => 'bi-journal-text', 'label' => __('lang.manage.ticket_fields')],
                ['icon' => 'bi-diagram-2', 'label' => __('lang.manage.approval_workflow')],
                ['icon' => 'bi-asterisk', 'label' => __('lang.manage.priority')],
                ['icon' => 'bi-list-check', 'label' => __('lang.manage.ticket_types')],
                ['icon' => 'bi-file-earmark-check', 'label' => __('lang.manage.daily_report')],
                ['icon' => 'bi-grid-1x2-fill', 'label' => __('lang.manage.admin_panel')],
            ],
            __('lang.sections.tickets') => [
                ['icon' => 'bi-file-earmark-post', 'label' => __('lang.tickets.settings')],
                ['icon' => 'bi-tag', 'label' => __('lang.tickets.status')],
                ['icon' => 'bi-lightning-charge-fill', 'label' => __('lang.tickets.labels')],
                ['icon' => 'bi-stars', 'label' => __('lang.tickets.ratings')],
                ['icon' => 'bi-diagram-3', 'label' => __('lang.tickets.workflow')],
                ['icon' => 'bi-tags', 'label' => __('lang.tickets.tags')],
                ['icon' => 'bi-check-square', 'label' => __('lang.tickets.auto_assignment')],
                ['icon' => 'bi-database', 'label' => __('lang.tickets.source')],
                ['icon' => 'bi-copy', 'label' => __('lang.tickets.recurring')],
                ['icon' => 'bi-map', 'label' => __('lang.tickets.location')],
                ['icon' => 'bi-book', 'label' => __('lang.tickets.template')],
                ['icon' => 'bi-filetype-pdf', 'label' => __('lang.tickets.pdf_template')],
            ],
            __('lang.sections.settings') => [
                ['icon' => 'bi-building', 'label' => __('lang.settings.company')],
                ['icon' => 'bi-laptop', 'label' => __('lang.settings.system')],
                ['icon' => 'bi-person', 'label' => __('lang.settings.contact')],
                ['icon' => 'bi-globe', 'label' => __('lang.settings.social_login')],
                ['icon' => 'bi-translate', 'label' => __('lang.settings.language')],
                ['icon' => 'bi-hourglass', 'label' => __('lang.settings.cron')],
                ['icon' => 'bi-safe', 'label' => __('lang.settings.security')],
                ['icon' => 'bi-folder', 'label' => __('lang.settings.filesystem')],
                ['icon' => 'bi-database-fill-gear', 'label' => __('lang.settings.backup')],
                ['icon' => 'bi-link-45deg', 'label' => __('lang.settings.social_widget')],
                ['icon' => 'bi-folder-symlink', 'label' => __('lang.settings.webhook')],
                ['icon' => 'bi-download', 'label' => __('lang.settings.user_import')],
                ['icon' => 'bi-arrow-repeat', 'label' => __('lang.settings.recaptcha')],
                ['icon' => 'bi-box-arrow-up-right', 'label' => __('lang.settings.login_log')],
                ['icon' => 'bi-funnel', 'label' => __('lang.settings.customer_filter')],
            ],
            __('lang.sections.addons') => [
                ['icon' => 'bi-plug', 'label' => __('lang.addons.plugins')],
                ['icon' => 'bi-link', 'label' => __('lang.addons.modules')],
            ],
            __('lang.sections.developer_settings') => [
                ['icon' => 'bi-gear-wide-connected', 'label' => __('lang.developer.api')],
                ['icon' => 'bi-cloud', 'label' => __('lang.developer.third_party_apps')],
                ['icon' => 'bi-box-arrow-in-right', 'label' => __('lang.developer.saml_integration')],
                ['icon' => 'bi-key', 'label' => __('lang.developer.personal_access_tokens')],
                ['icon' => 'bi-lock-fill', 'label' => __('lang.developer.oauth_app')],
                ['icon' => 'bi-display', 'label' => __('lang.developer.iframe_injection')],
            ],
            __('lang.sections.notifications') => [
                ['icon' => 'bi-file-earmark-break', 'label' => __('lang.notifications.template_management')],
                ['icon' => 'bi-mailbox', 'label' => __('lang.notifications.notification_management')],
                ['icon' => 'bi-bell', 'label' => __('lang.notifications.in_app_log')],
            ],
            __('lang.sections.billing') => [
                ['icon' => 'bi-wrench', 'label' => __('lang.billing.options')],
                ['icon' => 'bi-bag', 'label' => __('lang.billing.package')],
                ['icon' => 'bi-cash', 'label' => __('lang.billing.payment_gateway')],
            ],
            __('lang.sections.health_check') => [
                ['icon' => 'bi-heart-pulse', 'label' => __('lang.health_check.check')],
                ['icon' => 'bi-bell', 'label' => __('lang.health_check.alerts')],
                ['icon' => 'bi-bug-fill', 'label' => __('lang.health_check.debug_options')],
                ['icon' => 'bi-clock-history', 'label' => __('lang.health_check.system_logs')],
                ['icon' => 'bi-box-arrow-in-right', 'label' => __('lang.health_check.activity_logs')],
            ],
            __('lang.sections.attachment_scanner') => [
                ['icon' => 'bi-shield-lock', 'label' => __('lang.attachment_scanner.scanner_list')],
            ],
            __('lang.sections.additional_configs') => [
                ['icon' => 'bi-stopwatch-fill', 'label' => __('lang.additional_configs.time_tracking')],
                ['icon' => 'bi-filetype-css', 'label' => __('lang.additional_configs.custom_css')],
                ['icon' => 'bi-filetype-js', 'label' => __('lang.additional_configs.custom_js')],
                ['icon' => 'bi-facebook', 'label' => __('lang.additional_configs.facebook')],
                ['icon' => 'bi-bar-chart-line', 'label' => __('lang.additional_configs.lime_survey')],
                ['icon' => 'bi-chat-dots', 'label' => __('lang.additional_configs.slack')],
                ['icon' => 'bi-twitter-x', 'label' => __('lang.additional_configs.twitter_x')],
                ['icon' => 'bi-whatsapp', 'label' => __('lang.additional_configs.whatsapp')],
                ['icon' => 'bi-x-octagon-fill', 'label' => __('lang.additional_configs.zendesk')],
            ],
        ];

        return view('admin.dashboard', compact('sections'));
    }
}
