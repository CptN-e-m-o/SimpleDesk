<?php

return [
    // --- Index Page ---

    // Header Section
    'header_title' => 'Welcome to SimpleDesk',
    'header_subtitle' => 'A system for managing tickets and communicating with support.',
    'create_ticket_button' => 'Create Ticket',
    'login_button' => 'Log In',

    // "What SimpleDesk Can Do" Section
    'features_title' => 'What SimpleDesk Can Do',
    'features_client_title' => 'Client',
    'features_client_text' => 'Create tickets, track their status, and get help from the support team.',
    'features_agent_title' => 'Agent',
    'features_agent_text' => 'Take tickets into work, respond to clients, and help solve issues quickly.',
    'features_admin_title' => 'Administrator',
    'features_admin_text' => 'Manage users, assign roles, and monitor the efficiency of the support team.',

    // "How It Works" Section
    'how_it_works_title' => 'How It Works',
    'how_it_works_step1' => 'A user creates a ticket describing the issue.',
    'how_it_works_step2' => 'An agent takes the ticket and responds to the user.',
    'how_it_works_step3' => 'The discussion continues until the issue is resolved.',
    'how_it_works_step4' => 'The administrator oversees the process and manages roles.',

    // --- Login Page ---
    'login_title' => 'Login',
    'login_email_label' => 'Email',
    'login_password_label' => 'Password',
    'login_submit_button' => 'Log In',

    // --- Register Page ---
    'register_title' => 'Register',
    'register_name_label' => 'Name',
    'register_email_label' => 'Email',
    'register_password_label' => 'Password',
    'register_password_confirmation_label' => 'Password Confirmation',
    'register_submit_button' => 'Register',

    // --- Footer ---
    'footer_copyright' => '© :year SimpleDesk — Support Ticket System',

    // --- Navbar ---
    'navbar_brand' => 'SimpleDesk',
    'navbar_toggle_navigation' => 'Toggle Navigation',
    'navbar_login' => 'Login',
    'navbar_register' => 'Register',
    'navbar_my_tickets' => 'My Tickets',
    'navbar_all_tickets' => 'All Tickets',
    'navbar_users' => 'Users',
    'navbar_my_profile' => 'My Profile',
    'navbar_logout' => 'Logout',

    // --- App Level ---
    'app_title' => 'SimpleDesk — Support Ticket System',

    // --- Ticket Form ---
    'ticket_form_create_title' => 'Create New Ticket',
    'ticket_form_edit_title' => 'Edit Ticket #:id',
    'ticket_form_back_to_list' => 'Back to Ticket List',
    'ticket_form_subject_label' => 'Subject',
    'ticket_form_description_label' => 'Description',
    'ticket_form_agent_label' => 'Agent',
    'ticket_form_agent_none' => 'Unassigned',
    'ticket_form_priority_label' => 'Priority',
    'ticket_form_status_label' => 'Status',
    'ticket_form_cancel_button' => 'Cancel',
    'ticket_form_create_button' => 'Create Ticket',
    'ticket_form_save_button' => 'Save Changes',

    // --- Create Ticket Page ---
    'create_ticket_page_title' => 'Add New Ticket',

    // --- Edit Ticket Page ---
    'edit_ticket_page_title' => 'Edit Ticket',

    // --- Tickets List Page ---
    'tickets_list_title' => 'Ticket List',
    'tickets_list_add_button' => 'Add Ticket',
    'tickets_list_header_id' => 'ID',
    'tickets_list_header_subject' => 'Subject',
    'tickets_list_header_author' => 'Author',
    'tickets_list_header_status' => 'Status',
    'tickets_list_header_priority' => 'Priority',
    'tickets_list_header_created' => 'Created At',
    'tickets_list_header_actions' => 'Actions',
    'tickets_list_no_tickets_found' => 'No tickets found',
    'tickets_list_delete_confirm' => 'Are you sure you want to delete this ticket?',

    // --- Ticket Show Page ---
    'ticket_show_title' => 'Ticket #:id: :title',
    'ticket_show_description_title' => 'Description',
    'ticket_show_author_label' => 'Author:',
    'ticket_show_status_label' => 'Status:',
    'ticket_show_priority_label' => 'Priority:',
    'ticket_show_created_label' => 'Created At:',
    'ticket_show_replies_title' => 'Ticket Discussion',
    'ticket_show_edit_reply_button' => 'Edit',
    'ticket_show_delete_reply_button' => 'Delete',
    'ticket_show_delete_reply_confirm' => 'Delete this reply?',
    'ticket_show_no_replies' => 'There are no replies yet for this ticket.',
    'ticket_show_add_reply_title' => 'Add Reply',
    'ticket_show_reply_placeholder' => 'Write your reply here...',
    'ticket_show_send_reply_button' => 'Send',
    'ticket_show_ticket_closed_message' => 'This ticket is closed. Adding new replies is not allowed.',

    // --- User Form ---
    'user_form_name_label' => 'Name',
    'user_form_email_label' => 'E-mail',
    'user_form_role_label' => 'Role',
    'user_form_select_role_placeholder' => 'Select a role...',
    'user_form_password_label' => 'Password',
    'user_form_password_help' => 'Leave empty if you don’t want to change the password.',
    'user_form_password_confirmation_label' => 'Confirm Password',
    'user_form_cancel_button' => 'Cancel',
    'user_form_create_button' => 'Create User',
    'user_form_save_button' => 'Save Changes',

    // --- Create User Page ---
    'create_user_page_title' => 'Add New User',

    // --- Edit User Page ---
    'edit_user_page_title' => 'Edit User: :name',

    // --- Users List Page ---
    'users_list_title' => 'Users List',
    'users_list_add_button' => 'Add User',
    'users_list_header_id' => 'ID',
    'users_list_header_name' => 'Name',
    'users_list_header_email' => 'E-mail',
    'users_list_header_role' => 'Role',
    'users_list_header_actions' => 'Actions',
    'users_list_actions_aria_label' => 'User Actions',
    'users_list_view_button' => 'View',
    'users_list_edit_button' => 'Edit',
    'users_list_delete_button' => 'Delete',
    'users_list_delete_confirm' => 'Are you sure you want to delete this user?',
    'users_list_no_users_found' => 'No users found',

    // --- User Show Page ---
    'user_show_title' => 'User Profile',
    'user_show_back_to_list' => 'Back to Users List',
    'user_show_id_label' => 'User ID:',
    'user_show_email_label' => 'E-mail:',
    'user_show_registered_at_label' => 'Registered At:',
    'user_show_updated_at_label' => 'Last Updated:',
    'user_show_delete_confirm' => 'Are you sure you want to delete this user?',

    // --- Ticket Priorities ---
    'ticket_priority_low' => 'Low',
    'ticket_priority_medium' => 'Medium',
    'ticket_priority_high' => 'High',

    // --- Ticket Statuses ---
    'ticket_status_open' => 'Open',
    'ticket_status_in_progress' => 'In Progress',
    'ticket_status_closed' => 'Closed',

    // --- User Roles ---
    'user_role_client' => 'Client',
    'user_role_agent' => 'Agent',
    'user_role_admin' => 'Administrator',

    // --- Reply Controller Messages ---
    'reply_closed_ticket_error' => 'You cannot reply to a closed ticket.',
    'reply_added_success' => 'Your reply has been added.',
    'reply_updated_success' => 'Reply successfully updated.',
    'reply_deleted_success' => 'Reply deleted.',

    // --- Ticket Controller Messages ---
    'ticket_created_success' => 'Ticket successfully created!',
    'ticket_updated_success' => 'Ticket #:id successfully updated!',
    'ticket_deleted_success' => 'Ticket #:id successfully deleted.',

    // --- User Controller Messages ---
    'user_created_success' => 'User successfully created!',
    'user_updated_success' => 'User information successfully updated!',
    'user_deleted_success' => 'User successfully deleted!',
    'user_delete_permission_denied' => 'You do not have permission to perform this action.',
    'user_delete_self_error' => 'You cannot delete your own account!',

    // --- Profile Controller Messages ---
    'profile_updated' => 'Profile information has been successfully updated.',
    'profile_current_password_is_incorrect' => 'The current password is incorrect.',
    'profile_current_password_required' => 'The current password is required to set a new one.',
    'profile_avatar_updated' => 'Avatar updated successfully!',
    'profile_2fa_enabled_successfully' => 'Two-factor authentication has been enabled successfully!',
    'profile_2fa_disabled_successfully' => 'Two-factor authentication has been disabled.',
    'profile_2fa_incorrect_code' => 'The verification code is invalid. Please try again.',

    // --- Middleware Messages ---
    'access_denied' => 'You do not have permission to access this page.',
    'email_not_verified' => 'Your e-mail address is not verified.',

    // --- Validation Messages ---
    'validation_name_required' => 'The "Name" field is required.',
    'validation_email_required' => 'The "E-mail" field is required.',
    'validation_email_unique' => 'A user with this e-mail already exists.',
    'validation_password_required' => 'The "Password" field is required.',
    'validation_password_confirmed' => 'Passwords do not match.',
    'validation_role_required' => 'You must select a role for the user.',

    // --- User Profile ---
    'profile_tab_info' => 'User Profile',
    'profile_tab_2fa' => 'Two-Factor Authentication',
    'profile_tab_login_history' => 'Login History',
    'profile_first_name' => 'First Name',
    'profile_last_name' => 'Last Name',
    'profile_patronymic' => 'Patronymic',
    'profile_email' => 'Email Address',
    'profile_timezone' => 'Timezone',
    'profile_save_changes' => 'Save Changes',
    'profile_current_password' => 'Current Password',
    'profile_enter_password' => 'New Password',
    'profile_repeat_password' => 'Confirm Password',
    'profile_2fa_title' => 'Set Up Two-Factor Authentication',
    'profile_2fa_description' => 'Google Authenticator',
    'profile_enable_2fa' => 'Enable Two-Factor Authentication',
    'profile_login_history' => 'Account Login History',
    'profile_login_ip' => 'IP Address',
    'profile_login_device' => 'Device',
    'profile_login_time' => 'Login Time',
    'profile_2auth_enabled' => 'Two-factor authentication is enabled for your account.',
    'profile_disable_2auth' => 'Disable 2FA',
    'profile_step_1' => 'Step 1:',
    'profile_scan_qr_code' => 'Scan this QR code with your authenticator app (e.g., Google Authenticator).',
    'profile_enter_code_manually' => 'Or enter this key manually: ',
    'profile_step_2' => 'Step 2:',
    'profile_enter_code_finish_settings' => 'Enter the code from your app to complete the setup.',
    'profile_confirmation_code' => 'Verification Code',
    'no_login_history' => 'No login history found.',
    'profile_phone_number' => 'Phone number',
    'profile_signature' => 'Agent Signature',
    'profile_signature_help' => 'This signature will be automatically appended to your replies in tickets.',

    // Agent panel
    'dashboard' => 'Agent Dashboard',
    'open_tickets' => 'Open Tickets',
    'spam_tickets' => 'Spam',
    'closed_tickets' => 'Closed Tickets',
    'my_tickets' => 'My Tickets',
    'overdue_tickets' => 'Overdue Tickets',
    'unanswered_tickets' => 'Unanswered Tickets',
    'unassigned_tickets' => 'Unassigned Tickets',
    'tickets_assigned_to_me' => 'Tickets Assigned to Me',
    'pending_approvals' => 'Pending Approvals',

    // Ticket Status
    'open' => 'Open',
    'spam' => 'Spam',
    'closed' => 'Closed',
    'my' => 'My Tickets',
    'overdue' => 'Overdue',
    'unanswered' => 'Unanswered',
    'unassigned' => 'Unassigned',
    'assigned_to_me' => 'Assigned to Me',

    'sections' => [
        'users' => 'Users',
        'email' => 'Email',
        'drivers' => 'Drivers',
        'manage' => 'Manage',
        'tickets' => 'Tickets',
        'settings' => 'Settings',
        'addons' => 'Add-ons',
        'developer_settings' => 'Developer Settings',
        'notifications' => 'Notifications',
        'billing' => 'Billing',
        'health_check' => 'Health Check',
        'attachment_scanner' => 'Attachment Scanner',
        'additional_configs' => 'Additional Configurations',
    ],

    'users' => [
        'agents' => 'Agents',
        'departments' => 'Departments',
        'teams' => 'Teams',
    ],
    'email' => [
        'settings' => 'Mail Settings',
        'breaklines' => 'Breaklines',
        'diagnostics' => 'Diagnostics',
        'oauth' => 'OAuth Integration',
    ],
    'drivers' => [
        'queues' => 'Queues',
        'cache' => 'Cache Drivers',
        'websockets' => 'Web Sockets',
        'search' => 'Search',
    ],
    'manage' => [
        'automation' => 'Automation',
        'help_topics' => 'Help Topics',
        'sla_plans' => 'SLA Plans',
        'business_hours' => 'Business Hours',
        'forms' => 'Forms',
        'ticket_fields' => 'Ticket Fields',
        'approval_workflow' => 'Approval Workflow',
        'priority' => 'Priority',
        'ticket_types' => 'Ticket Types',
        'daily_report' => 'Daily Report',
        'admin_panel' => 'Admin Panel',
    ],
    'tickets' => [
        'settings' => 'Ticket Settings',
        'status' => 'Status',
        'labels' => 'Labels',
        'ratings' => 'Ratings',
        'workflow' => 'Ticket Workflow',
        'tags' => 'Tags',
        'auto_assignment' => 'Auto-assignment',
        'source' => 'Source',
        'recurring' => 'Recurring',
        'location' => 'Location',
        'template' => 'Ticket Template',
        'pdf_template' => 'PDF Ticket Template',
    ],
    'settings' => [
        'company' => 'Company',
        'system' => 'System',
        'contact' => 'Contact Settings',
        'social_login' => 'Social Login',
        'language' => 'Language',
        'cron' => 'Cron',
        'security' => 'Security',
        'filesystem' => 'File System',
        'backup' => 'System Backup',
        'social_widget' => 'Social Widget Settings',
        'webhook' => 'Webhook',
        'user_import' => 'User Import',
        'recaptcha' => 'ReCaptcha',
        'login_log' => 'Login Log Settings',
        'customer_filter' => 'Customer Filter',
    ],
    'addons' => [
        'plugins' => 'Plugins',
        'modules' => 'Modules',
    ],
    'developer' => [
        'api' => 'API',
        'third_party_apps' => 'Third-party Apps',
        'saml_integration' => 'SAML 2.0 Integration',
        'personal_access_tokens' => 'Personal Access Tokens',
        'oauth_app' => 'OAuth Application',
        'iframe_injection' => 'Iframe Injection',
    ],
    'notifications' => [
        'template_management' => 'Template Management',
        'notification_management' => 'Notification Management',
        'in_app_log' => 'In-app Log Management',
    ],
    'billing' => [
        'options' => 'Options',
        'package' => 'Package',
        'payment_gateway' => 'Payment Gateway',
    ],
    'health_check' => [
        'check' => 'Health Check',
        'alerts' => 'Health Check Alerts',
        'debug_options' => 'Debug Options',
        'system_logs' => 'System Logs',
        'activity_logs' => 'System-level Activity Logs',
    ],
    'attachment_scanner' => [
        'scanner_list' => 'Scanner List',
    ],
    'additional_configs' => [
        'time_tracking' => 'Time Tracking',
        'custom_css' => 'Custom CSS',
        'custom_js' => 'Custom JS',
        'facebook' => 'Facebook',
        'lime_survey' => 'Lime Survey Poll',
        'slack' => 'Slack',
        'twitter_x' => 'Twitter/X',
        'whatsapp' => 'WhatsApp',
        'zendesk' => 'Zendesk',
    ],
];
