<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\AgentStatusController;
use App\Http\Controllers\Admin\AgentStatusManagementController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\Mail\AttachmentAntivirusConnectionTestController;
use App\Http\Controllers\Admin\Mail\Diagnostics\MailDiagnosticsController as MailDiagnosticsPageController;
use App\Http\Controllers\Admin\Mail\EmailAttachmentDiagnosticsController;
use App\Http\Controllers\Admin\Mail\EmailAttachmentDownloadController;
use App\Http\Controllers\Admin\Mail\EmailAttachmentRejectionDiagnosticsController;
use App\Http\Controllers\Admin\Mail\EmailAttachmentRescanController;
use App\Http\Controllers\Admin\Mail\EmailMessageDiagnosticsController;
use App\Http\Controllers\Admin\Mail\EmailQuarantineDiagnosticsController;
use App\Http\Controllers\Admin\Mail\EmailQuarantineIgnoreController;
use App\Http\Controllers\Admin\Mail\EmailQuarantineRetryController;
use App\Http\Controllers\Admin\Mail\MailAdminAuditLogController;
use App\Http\Controllers\Admin\Mail\MailboxChannelConnectionTestController;
use App\Http\Controllers\Admin\Mail\MailboxChannelController;
use App\Http\Controllers\Admin\Mail\MailboxController;
use App\Http\Controllers\Admin\Mail\MailboxDiagnosticsController;
use App\Http\Controllers\Admin\Mail\MailboxManualSyncController;
use App\Http\Controllers\Admin\Mail\MailboxSettingsController;
use App\Http\Controllers\Admin\Mail\MailProviderConnectionController;
use App\Http\Controllers\Admin\Mail\MailProviderConnectionTestController;
use App\Http\Controllers\Admin\Mail\OAuth\MailOAuthAuthorizationController;
use App\Http\Controllers\Admin\Mail\OAuth\MailOAuthCallbackController;
use App\Http\Controllers\Admin\Mail\OAuth\MailOAuthConnectionTestController;
use App\Http\Controllers\Admin\Mail\OAuth\MailOAuthDisconnectController;
use App\Http\Controllers\Admin\Mail\OAuth\MailOAuthIntegrationController;
use App\Http\Controllers\Admin\Mail\OAuth\MailOAuthRefreshController;
use App\Http\Controllers\Admin\Mail\OutgoingEmailRetryController;
use App\Http\Controllers\Admin\Mail\ReplyParsingRuleController;
use App\Http\Controllers\Admin\Manage\TicketPriorityController;
use App\Http\Controllers\Admin\Manage\TicketTypeController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SkillController;
use App\Http\Controllers\Admin\System\Broadcasting\BroadcastBrowserProbeController;
use App\Http\Controllers\Admin\System\Broadcasting\BroadcastDriverController;
use App\Http\Controllers\Admin\System\Cache\CacheDeploymentActivationController;
use App\Http\Controllers\Admin\System\Cache\CacheDeploymentForceActivationController;
use App\Http\Controllers\Admin\System\Cache\CacheDriverActivationController;
use App\Http\Controllers\Admin\System\Cache\CacheDriverConfigurationController;
use App\Http\Controllers\Admin\System\Cache\CacheDriverConfigurationTestController;
use App\Http\Controllers\Admin\System\Cache\CacheDriverForceActivationController;
use App\Http\Controllers\Admin\System\DriverController;
use App\Http\Controllers\Admin\System\InfrastructureConnectionController;
use App\Http\Controllers\Admin\System\InfrastructureConnectionTestController;
use App\Http\Controllers\Admin\System\Queues\QueueDeploymentActivationController;
use App\Http\Controllers\Admin\System\Queues\QueueDeploymentForceActivationController;
use App\Http\Controllers\Admin\System\Queues\QueueDriverActivationController;
use App\Http\Controllers\Admin\System\Queues\QueueDriverConfigurationController;
use App\Http\Controllers\Admin\System\Queues\QueueDriverConfigurationTestController;
use App\Http\Controllers\Admin\System\Queues\QueueDriverForceActivationController;
use App\Http\Controllers\Admin\System\Search\SearchDriverController;
use App\Http\Controllers\Admin\System\Storage\StorageDriverController;
use App\Http\Controllers\Admin\System\SystemAuditLogController;
use App\Http\Controllers\Admin\TeamController;
use App\Http\Controllers\Admin\WorkScheduleAssignmentController;
use App\Http\Controllers\Admin\WorkScheduleController;
use App\Http\Controllers\Admin\WorkScheduleExceptionController;
use App\Http\Controllers\Agent\OwnAgentStatusController;
use App\Http\Controllers\Tickets\Agent\AgentTicketEmailReplyController;
use App\Http\Controllers\Tickets\User\TicketController;
use App\Http\Controllers\Tickets\User\TicketReplyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Home');
})->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/login', function () {
        return Inertia::render('Auth/Login');
    })->name('login');

    Route::get('/register', function () {
        return Inertia::render('Auth/Register');
    })->name('register');
});

Route::middleware('auth')->group(function () {
    Route::prefix('tickets')->name('tickets.')->group(function () {
        Route::get('/', [TicketController::class, 'index'])->name('index');
        Route::get('/create', [TicketController::class, 'create'])->name('create');
        Route::post('/', [TicketController::class, 'store'])->name('store');
        Route::get('/{ticket}', [TicketController::class, 'show'])->name('show');
    });

    Route::post('/tickets/{ticket}/replies', [TicketReplyController::class, 'store'])
        ->name('tickets.replies.store');

    Route::get(
        '/mail/attachments/{attachment}/download',
        EmailAttachmentDownloadController::class
    )->name('mail.attachments.download');

    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })
        ->middleware('permission:agent.tickets.visibility.assigned|agent.tickets.visibility.team|agent.tickets.visibility.department|agent.tickets.visibility.all')
        ->name('dashboard');

    Route::prefix('agent')->name('agent.')->group(function () {
        Route::post('/status', [OwnAgentStatusController::class, 'store'])->middleware('permission:agent.status.change_own')->name('status.store');
        Route::post('/status/default', [OwnAgentStatusController::class, 'default'])->middleware('permission:agent.status.change_own')->name('status.default');
        Route::get('/status/history', [OwnAgentStatusController::class, 'history'])->middleware('permission:agent.status.view_own_history')->name('status.history');
        Route::get('/tickets', function () {
            return Inertia::render('Tickets/Agent/Index');
        })
            ->middleware('permission:agent.tickets.visibility.assigned|agent.tickets.visibility.team|agent.tickets.visibility.department|agent.tickets.visibility.all')
            ->name('tickets');

        Route::post('/tickets/{ticket}/email-replies', [AgentTicketEmailReplyController::class, 'store'])
            ->middleware('permission:agent.tickets.reply')
            ->name('tickets.email-replies.store');
    });

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::prefix('system')->name('system.')->group(function (): void {
            Route::get('drivers', DriverController::class)->middleware('permission:admin.settings.drivers.view')->name('drivers.index');
            Route::get('drivers/real-time', [BroadcastDriverController::class, 'index'])->middleware('permission:admin.settings.broadcasting.view')->name('broadcasting.index');
            Route::get('drivers/real-time/create', [BroadcastDriverController::class, 'create'])->middleware('permission:admin.settings.broadcasting.create')->name('broadcasting.create');
            Route::post('drivers/real-time', [BroadcastDriverController::class, 'store'])->middleware('permission:admin.settings.broadcasting.create')->name('broadcasting.store');
            Route::post('drivers/real-time/deployment/activate', [BroadcastDriverController::class, 'activateDeployment'])->middleware('permission:admin.settings.broadcasting.activate')->name('broadcasting.activate-deployment');
            Route::post('drivers/real-time/deployment/force-activate', [BroadcastDriverController::class, 'forceActivateDeployment'])->middleware('permission:admin.settings.broadcasting.force_activate')->name('broadcasting.force-activate-deployment');
            Route::get('drivers/real-time/{configuration}/edit', [BroadcastDriverController::class, 'edit'])->middleware('permission:admin.settings.broadcasting.update')->name('broadcasting.edit');
            Route::put('drivers/real-time/{configuration}', [BroadcastDriverController::class, 'update'])->middleware('permission:admin.settings.broadcasting.update')->name('broadcasting.update');
            Route::patch('drivers/real-time/{configuration}/enabled', [BroadcastDriverController::class, 'enabled'])->middleware('permission:admin.settings.broadcasting.update')->name('broadcasting.enabled');
            Route::delete('drivers/real-time/{configuration}', [BroadcastDriverController::class, 'destroy'])->middleware('permission:admin.settings.broadcasting.archive')->name('broadcasting.destroy');
            Route::post('drivers/real-time/{id}/restore', [BroadcastDriverController::class, 'restore'])->middleware('permission:admin.settings.broadcasting.archive')->whereNumber('id')->name('broadcasting.restore');
            Route::delete('drivers/real-time/{id}/force-delete', [BroadcastDriverController::class, 'forceDelete'])->middleware('permission:admin.settings.broadcasting.delete')->whereNumber('id')->name('broadcasting.force-delete');
            Route::post('drivers/real-time/{configuration}/test', [BroadcastDriverController::class, 'test'])->middleware('permission:admin.settings.broadcasting.test')->name('broadcasting.test');
            Route::post('drivers/real-time/{configuration}/activate', [BroadcastDriverController::class, 'activate'])->middleware('permission:admin.settings.broadcasting.activate')->name('broadcasting.activate');
            Route::post('drivers/real-time/{configuration}/force-activate', [BroadcastDriverController::class, 'forceActivate'])->middleware('permission:admin.settings.broadcasting.force_activate')->name('broadcasting.force-activate');
            Route::get('drivers/search', [SearchDriverController::class, 'index'])->middleware('permission:admin.settings.search.view')->name('search.index');
            Route::get('drivers/search/create', [SearchDriverController::class, 'create'])->middleware('permission:admin.settings.search.create')->name('search.create');
            Route::post('drivers/search', [SearchDriverController::class, 'store'])->middleware('permission:admin.settings.search.create')->name('search.store');
            Route::post('drivers/search/deployment/activate', [SearchDriverController::class, 'activateDeployment'])->middleware('permission:admin.settings.search.activate')->name('search.activate-deployment');
            Route::post('drivers/search/deployment/force-activate', [SearchDriverController::class, 'forceActivateDeployment'])->middleware('permission:admin.settings.search.force_activate')->name('search.force-activate-deployment');
            Route::get('drivers/search/{configuration}/edit', [SearchDriverController::class, 'edit'])->middleware('permission:admin.settings.search.update')->name('search.edit');
            Route::put('drivers/search/{configuration}', [SearchDriverController::class, 'update'])->middleware('permission:admin.settings.search.update')->name('search.update');
            Route::patch('drivers/search/{configuration}/enabled', [SearchDriverController::class, 'enabled'])->middleware('permission:admin.settings.search.update')->name('search.enabled');
            Route::delete('drivers/search/{configuration}', [SearchDriverController::class, 'destroy'])->middleware('permission:admin.settings.search.archive')->name('search.destroy');
            Route::post('drivers/search/{id}/restore', [SearchDriverController::class, 'restore'])->middleware('permission:admin.settings.search.archive')->whereNumber('id')->name('search.restore');
            Route::delete('drivers/search/{id}/force-delete', [SearchDriverController::class, 'forceDelete'])->middleware('permission:admin.settings.search.delete')->whereNumber('id')->name('search.force-delete');
            Route::post('drivers/search/{configuration}/test', [SearchDriverController::class, 'test'])->middleware('permission:admin.settings.search.test')->name('search.test');
            Route::post('drivers/search/{configuration}/activate', [SearchDriverController::class, 'activate'])->middleware('permission:admin.settings.search.activate')->name('search.activate');
            Route::post('drivers/search/{configuration}/force-activate', [SearchDriverController::class, 'forceActivate'])->middleware('permission:admin.settings.search.force_activate')->name('search.force-activate');
            Route::get('drivers/storage', [StorageDriverController::class, 'index'])->middleware('permission:admin.settings.storage.view')->name('storage.index');
            Route::get('drivers/storage/create', [StorageDriverController::class, 'create'])->middleware('permission:admin.settings.storage.create')->name('storage.create');
            Route::post('drivers/storage', [StorageDriverController::class, 'store'])->middleware('permission:admin.settings.storage.create')->name('storage.store');
            Route::post('drivers/storage/deployment/activate', [StorageDriverController::class, 'activateDeployment'])->middleware('permission:admin.settings.storage.activate')->name('storage.activate-deployment');
            Route::post('drivers/storage/deployment/force-activate', [StorageDriverController::class, 'forceActivateDeployment'])->middleware('permission:admin.settings.storage.force_activate')->name('storage.force-activate-deployment');
            Route::get('drivers/storage/{configuration}/edit', [StorageDriverController::class, 'edit'])->middleware('permission:admin.settings.storage.update')->name('storage.edit');
            Route::put('drivers/storage/{configuration}', [StorageDriverController::class, 'update'])->middleware('permission:admin.settings.storage.update')->name('storage.update');
            Route::patch('drivers/storage/{configuration}/enabled', [StorageDriverController::class, 'enabled'])->middleware('permission:admin.settings.storage.update')->name('storage.enabled');
            Route::delete('drivers/storage/{configuration}', [StorageDriverController::class, 'destroy'])->middleware('permission:admin.settings.storage.archive')->name('storage.destroy');
            Route::post('drivers/storage/{id}/restore', [StorageDriverController::class, 'restore'])->middleware('permission:admin.settings.storage.archive')->whereNumber('id')->name('storage.restore');
            Route::delete('drivers/storage/{id}/force-delete', [StorageDriverController::class, 'forceDelete'])->middleware('permission:admin.settings.storage.delete')->whereNumber('id')->name('storage.force-delete');
            Route::post('drivers/storage/{configuration}/test', [StorageDriverController::class, 'test'])->middleware('permission:admin.settings.storage.test')->name('storage.test');
            Route::post('drivers/storage/{configuration}/activate', [StorageDriverController::class, 'activate'])->middleware('permission:admin.settings.storage.activate')->name('storage.activate');
            Route::post('drivers/storage/{configuration}/force-activate', [StorageDriverController::class, 'forceActivate'])->middleware('permission:admin.settings.storage.force_activate')->name('storage.force-activate');
            Route::get('drivers/cache', [CacheDriverConfigurationController::class, 'index'])->middleware('permission:admin.settings.cache.view')->name('cache.index');
            Route::get('drivers/cache/create', [CacheDriverConfigurationController::class, 'create'])->middleware('permission:admin.settings.cache.create')->name('cache.create');
            Route::post('drivers/cache', [CacheDriverConfigurationController::class, 'store'])->middleware('permission:admin.settings.cache.create')->name('cache.store');
            Route::post('drivers/cache/deployment/activate', CacheDeploymentActivationController::class)->middleware('permission:admin.settings.cache.activate')->name('cache.activate-deployment');
            Route::post('drivers/cache/deployment/force-activate', CacheDeploymentForceActivationController::class)->middleware('permission:admin.settings.cache.force_activate')->name('cache.force-activate-deployment');
            Route::get('drivers/cache/{configuration}/edit', [CacheDriverConfigurationController::class, 'edit'])->middleware('permission:admin.settings.cache.update')->name('cache.edit');
            Route::put('drivers/cache/{configuration}', [CacheDriverConfigurationController::class, 'update'])->middleware('permission:admin.settings.cache.update')->name('cache.update');
            Route::patch('drivers/cache/{configuration}/enabled', [CacheDriverConfigurationController::class, 'setEnabled'])->middleware('permission:admin.settings.cache.update')->name('cache.enabled');
            Route::delete('drivers/cache/{configuration}', [CacheDriverConfigurationController::class, 'destroy'])->middleware('permission:admin.settings.cache.archive')->name('cache.destroy');
            Route::post('drivers/cache/{id}/restore', [CacheDriverConfigurationController::class, 'restore'])->middleware('permission:admin.settings.cache.archive')->whereNumber('id')->name('cache.restore');
            Route::delete('drivers/cache/{id}/force-delete', [CacheDriverConfigurationController::class, 'forceDelete'])->middleware('permission:admin.settings.cache.delete')->whereNumber('id')->name('cache.force-delete');
            Route::post('drivers/cache/{configuration}/test', CacheDriverConfigurationTestController::class)->middleware('permission:admin.settings.cache.test')->name('cache.test');
            Route::post('drivers/cache/{configuration}/activate', CacheDriverActivationController::class)->middleware('permission:admin.settings.cache.activate')->name('cache.activate');
            Route::post('drivers/cache/{configuration}/force-activate', CacheDriverForceActivationController::class)->middleware('permission:admin.settings.cache.force_activate')->name('cache.force-activate');
            Route::get('drivers/queues', [QueueDriverConfigurationController::class, 'index'])->middleware('permission:admin.settings.queues.view')->name('queues.index');
            Route::get('drivers/queues/create', [QueueDriverConfigurationController::class, 'create'])->middleware('permission:admin.settings.queues.create')->name('queues.create');
            Route::post('drivers/queues', [QueueDriverConfigurationController::class, 'store'])->middleware('permission:admin.settings.queues.create')->name('queues.store');
            Route::post('drivers/queues/deployment/activate', QueueDeploymentActivationController::class)
                ->middleware('permission:admin.settings.queues.activate')
                ->name('queues.activate-deployment');

            Route::post('drivers/queues/deployment/force-activate', QueueDeploymentForceActivationController::class)
                ->middleware('permission:admin.settings.queues.force_activate')
                ->name('queues.force-activate-deployment');
            Route::get('drivers/queues/{configuration}/edit', [QueueDriverConfigurationController::class, 'edit'])->middleware('permission:admin.settings.queues.update')->name('queues.edit');
            Route::put('drivers/queues/{configuration}', [QueueDriverConfigurationController::class, 'update'])->middleware('permission:admin.settings.queues.update')->name('queues.update');
            Route::patch('drivers/queues/{configuration}/enabled', [QueueDriverConfigurationController::class, 'setEnabled'])->middleware('permission:admin.settings.queues.update')->name('queues.enabled');
            Route::delete('drivers/queues/{configuration}', [QueueDriverConfigurationController::class, 'destroy'])->middleware('permission:admin.settings.queues.archive')->name('queues.destroy');
            Route::post('drivers/queues/{id}/restore', [QueueDriverConfigurationController::class, 'restore'])->middleware('permission:admin.settings.queues.archive')->whereNumber('id')->name('queues.restore');
            Route::delete('drivers/queues/{id}/force-delete', [QueueDriverConfigurationController::class, 'forceDelete'])->middleware('permission:admin.settings.queues.delete')->whereNumber('id')->name('queues.force-delete');
            Route::post('drivers/queues/{configuration}/test', QueueDriverConfigurationTestController::class)->middleware('permission:admin.settings.queues.test')->name('queues.test');
            Route::post('drivers/queues/{configuration}/activate', QueueDriverActivationController::class)
                ->middleware('permission:admin.settings.queues.activate')
                ->name('queues.activate');
            Route::post('drivers/queues/{configuration}/force-activate', QueueDriverForceActivationController::class)
                ->middleware('permission:admin.settings.queues.force_activate')
                ->name('queues.force-activate');
            Route::get('connections', [InfrastructureConnectionController::class, 'index'])->middleware('permission:admin.settings.infrastructure_connections.view')->name('connections.index');
            Route::get('connections/create', [InfrastructureConnectionController::class, 'create'])->middleware('permission:admin.settings.infrastructure_connections.create')->name('connections.create');
            Route::post('connections', [InfrastructureConnectionController::class, 'store'])->middleware('permission:admin.settings.infrastructure_connections.create')->name('connections.store');
            Route::get('connections/{connection}/edit', [InfrastructureConnectionController::class, 'edit'])->middleware('permission:admin.settings.infrastructure_connections.update')->name('connections.edit');
            Route::put('connections/{connection}', [InfrastructureConnectionController::class, 'update'])->middleware('permission:admin.settings.infrastructure_connections.update')->name('connections.update');
            Route::patch('connections/{connection}/toggle', [InfrastructureConnectionController::class, 'toggle'])->middleware('permission:admin.settings.infrastructure_connections.update')->name('connections.toggle');
            Route::post('connections/{connection}/test', InfrastructureConnectionTestController::class)->middleware('permission:admin.settings.infrastructure_connections.test')->name('connections.test');
            Route::delete('connections/{connection}', [InfrastructureConnectionController::class, 'destroy'])->middleware('permission:admin.settings.infrastructure_connections.archive')->name('connections.destroy');
            Route::post('connections/{id}/restore', [InfrastructureConnectionController::class, 'restore'])->middleware('permission:admin.settings.infrastructure_connections.archive')->whereNumber('id')->name('connections.restore');
            Route::delete('connections/{id}/force-delete', [InfrastructureConnectionController::class, 'forceDelete'])->middleware('permission:admin.settings.infrastructure_connections.delete')->whereNumber('id')->name('connections.force-delete');
            Route::get('audit', SystemAuditLogController::class)->middleware('permission:admin.settings.system_audit.view')->name('audit.index');
            Route::post(
                'drivers/real-time/browser-probe',
                BroadcastBrowserProbeController::class,
            )
                ->middleware([
                    'permission:admin.settings.broadcasting.test',
                    'throttle:10,1',
                ])
                ->name('broadcasting.browser-probe');
        });
        Route::get('/dashboard', function () {
            return Inertia::render('Admin/Dashboard');
        })
            ->middleware('permission:admin.manage.manage_dashboard')
            ->name('dashboard');

        Route::prefix('manage')->name('manage.')->group(function (): void {
            Route::get('priorities', [TicketPriorityController::class, 'index'])->middleware('permission:admin.manage.priorities.view')->name('priorities.index');
            Route::get('priorities/create', [TicketPriorityController::class, 'create'])->middleware('permission:admin.manage.priorities.create')->name('priorities.create');
            Route::post('priorities', [TicketPriorityController::class, 'store'])->middleware('permission:admin.manage.priorities.create')->name('priorities.store');
            Route::get('priorities/{priority}/edit', [TicketPriorityController::class, 'edit'])->middleware('permission:admin.manage.priorities.update')->name('priorities.edit');
            Route::put('priorities/{priority}', [TicketPriorityController::class, 'update'])->middleware('permission:admin.manage.priorities.update')->name('priorities.update');
            Route::patch('priorities/{priority}/enabled', [TicketPriorityController::class, 'enabled'])->middleware('permission:admin.manage.priorities.update')->name('priorities.enabled');
            Route::patch('priorities/{priority}/default', [TicketPriorityController::class, 'makeDefault'])->middleware('permission:admin.manage.priorities.update')->name('priorities.default');
            Route::patch('priorities/reorder', [TicketPriorityController::class, 'reorder'])->middleware('permission:admin.manage.priorities.update')->name('priorities.reorder');
            Route::delete('priorities/{priority}', [TicketPriorityController::class, 'destroy'])->middleware('permission:admin.manage.priorities.archive')->name('priorities.destroy');
            Route::post('priorities/{priority}/restore', [TicketPriorityController::class, 'restore'])->middleware('permission:admin.manage.priorities.archive')->whereNumber('priority')->name('priorities.restore');

            Route::get('ticket-types', [TicketTypeController::class, 'index'])->middleware('permission:admin.manage.ticket_types.view')->name('ticket-types.index');
            Route::get('ticket-types/create', [TicketTypeController::class, 'create'])->middleware('permission:admin.manage.ticket_types.create')->name('ticket-types.create');
            Route::post('ticket-types', [TicketTypeController::class, 'store'])->middleware('permission:admin.manage.ticket_types.create')->name('ticket-types.store');
            Route::get('ticket-types/{ticketType}/edit', [TicketTypeController::class, 'edit'])->middleware('permission:admin.manage.ticket_types.update')->name('ticket-types.edit');
            Route::put('ticket-types/{ticketType}', [TicketTypeController::class, 'update'])->middleware('permission:admin.manage.ticket_types.update')->name('ticket-types.update');
            Route::patch('ticket-types/{ticketType}/enabled', [TicketTypeController::class, 'enabled'])->middleware('permission:admin.manage.ticket_types.update')->name('ticket-types.enabled');
            Route::patch('ticket-types/reorder', [TicketTypeController::class, 'reorder'])->middleware('permission:admin.manage.ticket_types.update')->name('ticket-types.reorder');
            Route::delete('ticket-types/{ticketType}', [TicketTypeController::class, 'destroy'])->middleware('permission:admin.manage.ticket_types.archive')->name('ticket-types.destroy');
            Route::post('ticket-types/{ticketType}/restore', [TicketTypeController::class, 'restore'])->middleware('permission:admin.manage.ticket_types.archive')->whereNumber('ticketType')->name('ticket-types.restore');
        });

        Route::resource('teams', TeamController::class)
            ->middleware('permission:admin.staff.manage_teams');

        Route::patch('/teams/{team}/restore', [TeamController::class, 'restore'])
            ->middleware('permission:admin.staff.manage_teams')
            ->name('teams.restore')
            ->withTrashed();

        Route::delete('/teams/{team}/force-delete', [TeamController::class, 'forceDelete'])
            ->middleware('super_admin')
            ->name('teams.force-delete')
            ->withTrashed();

        Route::resource('departments', DepartmentController::class)
            ->middleware('permission:admin.staff.manage_departments');

        Route::patch('/departments/{department}/restore', [DepartmentController::class, 'restore'])
            ->middleware('permission:admin.staff.manage_departments')
            ->name('departments.restore')
            ->withTrashed();

        Route::delete('/departments/{department}/force-delete', [DepartmentController::class, 'forceDelete'])
            ->middleware('super_admin')
            ->name('departments.force-delete')
            ->withTrashed();

        Route::get('roles/create/{type}', [RoleController::class, 'create'])
            ->whereIn('type', ['user', 'agent'])
            ->middleware('permission:admin.staff.manage_roles')
            ->name('roles.create.typed');

        Route::patch('roles/{role}/restore', [RoleController::class, 'restore'])
            ->withTrashed()
            ->middleware('permission:admin.staff.manage_roles')
            ->name('roles.restore');

        Route::delete('roles/{role}/force-delete', [RoleController::class, 'forceDelete'])
            ->withTrashed()
            ->middleware('super_admin')
            ->name('roles.force-delete');

        Route::resource('roles', RoleController::class)
            ->except(['show'])
            ->middleware('permission:admin.staff.manage_roles');

        Route::resource('agents', AgentController::class)
            ->withTrashed(['show', 'edit', 'update'])
            ->middleware('permission:admin.staff.manage_agents');

        Route::get('skills', [SkillController::class, 'index'])->middleware('permission:admin.staff.skills.view')->name('skills.index');
        Route::get('skills/create', [SkillController::class, 'create'])->middleware('permission:admin.staff.skills.create')->name('skills.create');
        Route::post('skills', [SkillController::class, 'store'])->middleware('permission:admin.staff.skills.create')->name('skills.store');
        Route::get('skills/{skill}/edit', [SkillController::class, 'edit'])->middleware('permission:admin.staff.skills.update')->whereNumber('skill')->name('skills.edit');
        Route::put('skills/{skill}', [SkillController::class, 'update'])->middleware('permission:admin.staff.skills.update')->whereNumber('skill')->name('skills.update');
        Route::post('skills/{skill}/duplicate', [SkillController::class, 'duplicate'])->middleware('permission:admin.staff.skills.create')->whereNumber('skill')->name('skills.duplicate');
        Route::patch('skills/{skill}/toggle', [SkillController::class, 'toggle'])->middleware('permission:admin.staff.skills.update')->whereNumber('skill')->name('skills.toggle');
        Route::delete('skills/{skill}', [SkillController::class, 'destroy'])->middleware('permission:admin.staff.skills.archive')->whereNumber('skill')->name('skills.destroy');
        Route::post('skills/{skill}/restore', [SkillController::class, 'restore'])->middleware('permission:admin.staff.skills.archive')->whereNumber('skill')->name('skills.restore');
        Route::delete('skills/{skill}/force-delete', [SkillController::class, 'forceDelete'])->middleware('permission:admin.staff.skills.delete')->whereNumber('skill')->name('skills.force-delete');

        Route::get('agent-statuses', [AgentStatusController::class, 'index'])->middleware('permission:admin.staff.agent_statuses.view')->name('agent-statuses.index');
        Route::get('agent-statuses/create', [AgentStatusController::class, 'create'])->middleware('permission:admin.staff.agent_statuses.create')->name('agent-statuses.create');
        Route::post('agent-statuses', [AgentStatusController::class, 'store'])->middleware('permission:admin.staff.agent_statuses.create')->name('agent-statuses.store');
        Route::get('agent-statuses/{agentStatus}/edit', [AgentStatusController::class, 'edit'])->middleware('permission:admin.staff.agent_statuses.update')->name('agent-statuses.edit');
        Route::put('agent-statuses/{agentStatus}', [AgentStatusController::class, 'update'])->middleware('permission:admin.staff.agent_statuses.update')->name('agent-statuses.update');
        Route::post('agent-statuses/{agentStatus}/duplicate', [AgentStatusController::class, 'duplicate'])->middleware('permission:admin.staff.agent_statuses.create')->name('agent-statuses.duplicate');
        Route::patch('agent-statuses/{agentStatus}/toggle', [AgentStatusController::class, 'toggle'])->middleware('permission:admin.staff.agent_statuses.update')->name('agent-statuses.toggle');
        Route::patch('agent-statuses/{agentStatus}/default', [AgentStatusController::class, 'makeDefault'])->middleware('permission:admin.staff.agent_statuses.update')->name('agent-statuses.default');
        Route::delete('agent-statuses/{agentStatus}', [AgentStatusController::class, 'destroy'])->middleware('permission:admin.staff.agent_statuses.archive')->name('agent-statuses.destroy');
        Route::post('agent-statuses/{agentStatus}/restore', [AgentStatusController::class, 'restore'])->middleware('permission:admin.staff.agent_statuses.archive')->name('agent-statuses.restore');
        Route::delete(
            'agent-statuses/{agentStatus}/force-delete',
            [AgentStatusController::class, 'forceDelete']
        )
            ->middleware(
                'permission:admin.staff.agent_statuses.delete'
            )
            ->whereNumber('agentStatus')
            ->name('agent-statuses.force-delete');
        Route::get('agents/{agent}/status', [AgentStatusManagementController::class, 'show'])->middleware('permission:admin.staff.agent_statuses.manage_agents')->name('agents.status.show');
        Route::post('agents/{agent}/status', [AgentStatusManagementController::class, 'store'])->middleware('permission:admin.staff.agent_statuses.manage_agents')->name('agents.status.store');
        Route::post('agents/{agent}/status/default', [AgentStatusManagementController::class, 'default'])->middleware('permission:admin.staff.agent_statuses.manage_agents')->name('agents.status.default');
        Route::get('agents/{agent}/status/history', [AgentStatusManagementController::class, 'history'])->middleware('permission:admin.staff.agent_statuses.view_history')->name('agents.status.history');

        Route::get('work-schedules', [WorkScheduleController::class, 'index'])->middleware('permission:admin.staff.work_schedules.view')->name('work-schedules.index');
        Route::get('work-schedules/create', [WorkScheduleController::class, 'create'])->middleware('permission:admin.staff.work_schedules.create')->name('work-schedules.create');
        Route::post('work-schedules', [WorkScheduleController::class, 'store'])->middleware('permission:admin.staff.work_schedules.create')->name('work-schedules.store');
        Route::get('work-schedules/{workSchedule}', [WorkScheduleController::class, 'show'])->middleware('permission:admin.staff.work_schedules.view')->whereNumber('workSchedule')->name('work-schedules.show');
        Route::get('work-schedules/{workSchedule}/edit', [WorkScheduleController::class, 'edit'])->middleware('permission:admin.staff.work_schedules.update')->whereNumber('workSchedule')->name('work-schedules.edit');
        Route::put('work-schedules/{workSchedule}', [WorkScheduleController::class, 'update'])->middleware('permission:admin.staff.work_schedules.update')->whereNumber('workSchedule')->name('work-schedules.update');
        Route::post('work-schedules/{workSchedule}/duplicate', [WorkScheduleController::class, 'duplicate'])->middleware('permission:admin.staff.work_schedules.create')->whereNumber('workSchedule')->name('work-schedules.duplicate');
        Route::patch('work-schedules/{workSchedule}/toggle', [WorkScheduleController::class, 'toggle'])->middleware('permission:admin.staff.work_schedules.update')->whereNumber('workSchedule')->name('work-schedules.toggle');
        Route::delete('work-schedules/{workSchedule}', [WorkScheduleController::class, 'destroy'])->middleware('permission:admin.staff.work_schedules.archive')->whereNumber('workSchedule')->name('work-schedules.destroy');
        Route::post('work-schedules/{workSchedule}/restore', [WorkScheduleController::class, 'restore'])->middleware('permission:admin.staff.work_schedules.archive')->whereNumber('workSchedule')->name('work-schedules.restore');
        Route::post('work-schedules/{workSchedule}/assignments', [WorkScheduleAssignmentController::class, 'store'])->middleware('permission:admin.staff.work_schedules.manage_assignments')->whereNumber('workSchedule')->name('work-schedules.assignments.store');
        Route::put('work-schedule-assignments/{assignment}', [WorkScheduleAssignmentController::class, 'update'])->middleware('permission:admin.staff.work_schedules.manage_assignments')->whereNumber('assignment')->name('work-schedule-assignments.update');
        Route::patch('work-schedule-assignments/{assignment}/end', [WorkScheduleAssignmentController::class, 'end'])->middleware('permission:admin.staff.work_schedules.manage_assignments')->whereNumber('assignment')->name('work-schedule-assignments.end');
        Route::delete('work-schedule-assignments/{assignment}', [WorkScheduleAssignmentController::class, 'destroy'])->middleware('permission:admin.staff.work_schedules.manage_assignments')->whereNumber('assignment')->name('work-schedule-assignments.destroy');
        Route::get('work-schedule-assignments/{assignment}/exceptions', [WorkScheduleExceptionController::class, 'index'])->middleware('permission:admin.staff.work_schedules.manage_exceptions')->whereNumber('assignment')->name('work-schedule-exceptions.index');
        Route::post('work-schedule-assignments/{assignment}/exceptions', [WorkScheduleExceptionController::class, 'store'])->middleware('permission:admin.staff.work_schedules.manage_exceptions')->whereNumber('assignment')->name('work-schedule-exceptions.store');
        Route::put('work-schedule-exceptions/{exception}', [WorkScheduleExceptionController::class, 'update'])->middleware('permission:admin.staff.work_schedules.manage_exceptions')->whereNumber('exception')->name('work-schedule-exceptions.update');
        Route::delete('work-schedule-exceptions/{exception}', [WorkScheduleExceptionController::class, 'destroy'])->middleware('permission:admin.staff.work_schedules.manage_exceptions')->whereNumber('exception')->name('work-schedule-exceptions.destroy');

        Route::get('users/{agent}', [AgentController::class, 'showUser'])
            ->withTrashed()
            ->middleware('permission:admin.staff.manage_agents')
            ->name('users.show');

        Route::patch('agents/{agent}/restore', [AgentController::class, 'restore'])
            ->withTrashed()
            ->middleware('permission:admin.staff.manage_agents')
            ->name('agents.restore');

        Route::delete('agents/{agent}/force-delete', [AgentController::class, 'forceDelete'])
            ->withTrashed()
            ->middleware('super_admin')
            ->name('agents.force-delete');

        Route::prefix('email')->name('email.')->group(function () {
            /*
             * Frontend routes
             */

            Route::get(
                '/settings',
                [MailboxSettingsController::class, 'index']
            )
                ->middleware(
                    'permission:admin.mail.view|admin.mail.manage_mailboxes'
                )
                ->name('settings.index');

            Route::get(
                '/settings/mailboxes/create',
                [MailboxSettingsController::class, 'create']
            )
                ->middleware(
                    'permission:admin.mail.manage_mailboxes'
                )
                ->name('settings.mailboxes.create');

            Route::post(
                '/settings/mailboxes',
                [MailboxSettingsController::class, 'store']
            )
                ->middleware([
                    'permission:admin.mail.manage_mailboxes',
                    'mail.audit:mailbox_created',
                ])
                ->name('settings.mailboxes.store');

            Route::get(
                '/settings/mailboxes/{mailbox}',
                [MailboxSettingsController::class, 'show']
            )
                ->middleware(
                    'permission:admin.mail.view|admin.mail.manage_mailboxes'
                )
                ->whereNumber('mailbox')
                ->name('settings.mailboxes.show');

            Route::post(
                '/settings/mailboxes/{mailbox}/restore',
                [MailboxSettingsController::class, 'restore']
            )
                ->middleware([
                    'permission:admin.mail.manage_mailboxes',
                    'mail.audit:mailbox_restored',
                ])
                ->whereNumber('mailbox')
                ->name('settings.mailboxes.restore');

            Route::delete(
                '/settings/mailboxes/{mailbox}/force',
                [MailboxSettingsController::class, 'forceDestroy']
            )
                ->middleware([
                    'permission:admin.mail.manage_mailboxes',
                    'mail.audit:mailbox_force_deleted',
                ])
                ->whereNumber('mailbox')
                ->name('settings.mailboxes.force-destroy');

            Route::get(
                '/settings/mailboxes/{mailbox}/edit',
                [MailboxSettingsController::class, 'edit']
            )
                ->middleware(
                    'permission:admin.mail.manage_mailboxes'
                )
                ->whereNumber('mailbox')
                ->name('settings.mailboxes.edit');

            Route::put(
                '/settings/mailboxes/{mailbox}',
                [MailboxSettingsController::class, 'update']
            )
                ->middleware([
                    'permission:admin.mail.manage_mailboxes',
                    'mail.audit:mailbox_updated',
                ])
                ->whereNumber('mailbox')
                ->name('settings.mailboxes.update');

            Route::get(
                '/settings/mailboxes/{mailbox}/setup/incoming',
                [MailboxSettingsController::class, 'incoming']
            )
                ->middleware(
                    'permission:admin.mail.manage_channels'
                )
                ->whereNumber('mailbox')
                ->name('settings.mailboxes.setup.incoming');

            Route::post(
                '/settings/mailboxes/{mailbox}/setup/incoming',
                [MailboxSettingsController::class, 'storeIncoming']
            )
                ->middleware([
                    'permission:admin.mail.manage_channels',
                    'mail.audit:channel_created',
                ])
                ->whereNumber('mailbox')
                ->name('settings.mailboxes.setup.incoming.store');

            Route::get(
                '/settings/mailboxes/{mailbox}/setup/outgoing',
                [MailboxSettingsController::class, 'outgoing']
            )
                ->middleware(
                    'permission:admin.mail.manage_channels'
                )
                ->whereNumber('mailbox')
                ->name('settings.mailboxes.setup.outgoing');

            Route::post(
                '/settings/mailboxes/{mailbox}/setup/outgoing',
                [MailboxSettingsController::class, 'storeOutgoing']
            )
                ->middleware([
                    'permission:admin.mail.manage_channels',
                    'mail.audit:channel_created',
                ])
                ->whereNumber('mailbox')
                ->name('settings.mailboxes.setup.outgoing.store');

            Route::get(
                '/settings/mailboxes/{mailbox}/setup/review',
                [MailboxSettingsController::class, 'review']
            )
                ->middleware(
                    'permission:admin.mail.manage_mailboxes'
                )
                ->whereNumber('mailbox')
                ->name('settings.mailboxes.setup.review');

            Route::post(
                '/settings/mailboxes/{mailbox}/setup/finish',
                [MailboxSettingsController::class, 'finish']
            )
                ->middleware(
                    'permission:admin.mail.manage_mailboxes'
                )
                ->whereNumber('mailbox')
                ->name('settings.mailboxes.setup.finish');

            Route::delete(
                '/settings/mailboxes/{mailbox}',
                [MailboxSettingsController::class, 'destroy']
            )
                ->middleware([
                    'permission:admin.mail.manage_mailboxes',
                    'mail.audit:mailbox_deleted',
                ])
                ->whereNumber('mailbox')
                ->name('settings.mailboxes.destroy');

            Route::get('/reply-parsing', [ReplyParsingRuleController::class, 'index'])
                ->middleware('permission:admin.mail.view_reply_parsing|admin.mail.manage_reply_parsing')
                ->name('reply-parsing.index');

            Route::get('/reply-parsing/create', [ReplyParsingRuleController::class, 'create'])
                ->middleware('permission:admin.mail.manage_reply_parsing')
                ->name('reply-parsing.create');

            Route::post('/reply-parsing', [ReplyParsingRuleController::class, 'store'])
                ->middleware([
                    'permission:admin.mail.manage_reply_parsing',
                    'mail.audit:reply_parsing_rule_created',
                ])
                ->name('reply-parsing.store');

            Route::get('/reply-parsing/{rule}/edit', [ReplyParsingRuleController::class, 'edit'])
                ->middleware('permission:admin.mail.manage_reply_parsing')
                ->whereNumber('rule')
                ->name('reply-parsing.edit');

            Route::put('/reply-parsing/{rule}', [ReplyParsingRuleController::class, 'update'])
                ->middleware([
                    'permission:admin.mail.manage_reply_parsing',
                    'mail.audit:reply_parsing_rule_updated',
                ])
                ->whereNumber('rule')
                ->name('reply-parsing.update');

            Route::delete('/reply-parsing/{rule}', [ReplyParsingRuleController::class, 'destroy'])
                ->middleware([
                    'permission:admin.mail.manage_reply_parsing',
                    'mail.audit:reply_parsing_rule_deleted',
                ])
                ->whereNumber('rule')
                ->name('reply-parsing.destroy');

            Route::post('/reply-parsing/{rule}/restore', [ReplyParsingRuleController::class, 'restore'])
                ->middleware([
                    'permission:admin.mail.manage_reply_parsing',
                    'mail.audit:reply_parsing_rule_restored',
                ])
                ->whereNumber('rule')
                ->name('reply-parsing.restore');

            Route::delete('/reply-parsing/{rule}/force', [ReplyParsingRuleController::class, 'forceDestroy'])
                ->middleware([
                    'permission:admin.mail.manage_reply_parsing',
                    'mail.audit:reply_parsing_rule_force_deleted',
                ])
                ->whereNumber('rule')
                ->name('reply-parsing.force-destroy');

            Route::post('/reply-parsing/preview', [ReplyParsingRuleController::class, 'preview'])
                ->middleware('permission:admin.mail.manage_reply_parsing')
                ->name('reply-parsing.preview');
            /*
             * Backend routes
             */
            Route::get('/audit-logs', MailAdminAuditLogController::class)
                ->middleware('permission:admin.mail.view_audit')
                ->name('audit-logs.index');

            Route::get('/diagnostics', [MailDiagnosticsPageController::class, 'index'])
                ->middleware('permission:admin.mail.view_diagnostics|admin.mail.test_connections|admin.mail.view')
                ->name('diagnostics.index');

            Route::post('/diagnostics/channels/{channel}/test', MailboxChannelConnectionTestController::class)
                ->middleware([
                    'permission:admin.mail.test_connections',
                    'mail.audit:channel_connection_tested',
                ])
                ->whereNumber('channel')
                ->name('diagnostics.channels.test');

            Route::post('/diagnostics/antivirus/test', AttachmentAntivirusConnectionTestController::class)
                ->middleware([
                    'permission:admin.mail.test_connections',
                    'mail.audit:antivirus_connection_tested',
                ])
                ->name('diagnostics.antivirus.test');

            Route::get('/mailboxes/{mailbox}/diagnostics', MailboxDiagnosticsController::class)
                ->middleware('permission:admin.mail.view_diagnostics')
                ->name('mailboxes.diagnostics');

            Route::get('/messages', EmailMessageDiagnosticsController::class)
                ->middleware('permission:admin.mail.view_diagnostics')
                ->name('diagnostics.messages');

            Route::get('/attachments', EmailAttachmentDiagnosticsController::class)
                ->middleware('permission:admin.mail.view_diagnostics')
                ->name('diagnostics.attachments');

            Route::get('/quarantines', EmailQuarantineDiagnosticsController::class)
                ->middleware('permission:admin.mail.view_diagnostics')
                ->name('diagnostics.quarantines');

            Route::get('/rejected-attachments', EmailAttachmentRejectionDiagnosticsController::class)
                ->middleware('permission:admin.mail.view_diagnostics')
                ->name('diagnostics.rejected-attachments');

            Route::post('/mailboxes/{mailbox}/sync', MailboxManualSyncController::class)
                ->middleware([
                    'permission:admin.mail.sync_mailboxes',
                    'mail.audit:mailbox_sync_requested',
                ])
                ->name('mailboxes.sync');

            Route::post('/messages/{message}/retry', OutgoingEmailRetryController::class)
                ->middleware([
                    'permission:admin.mail.retry_messages',
                    'mail.audit:outgoing_message_retry_requested',
                ])
                ->name('messages.retry');

            Route::post('/attachments/{attachment}/rescan', EmailAttachmentRescanController::class)
                ->middleware([
                    'permission:admin.mail.rescan_attachments',
                    'mail.audit:attachment_rescan_requested',
                ])
                ->name('attachments.rescan');

            Route::post('/quarantines/{quarantine}/retry', EmailQuarantineRetryController::class)
                ->middleware([
                    'permission:admin.mail.manage_quarantine',
                    'mail.audit:quarantine_retry_requested',
                ])
                ->name('quarantines.retry');

            Route::post('/quarantines/{quarantine}/ignore', EmailQuarantineIgnoreController::class)
                ->middleware([
                    'permission:admin.mail.manage_quarantine',
                    'mail.audit:quarantine_ignored',
                ])
                ->name('quarantines.ignore');

            Route::post('/channels/{channel}/test', MailboxChannelConnectionTestController::class)
                ->middleware([
                    'permission:admin.mail.test_connections',
                    'mail.audit:channel_connection_tested',
                ])
                ->name('channels.test');

            Route::post('/provider-connections/{providerConnection}/test', MailProviderConnectionTestController::class)
                ->middleware([
                    'permission:admin.mail.test_connections',
                    'mail.audit:provider_connection_tested',
                ])
                ->name('provider-connections.test');

            Route::post('/antivirus/test', AttachmentAntivirusConnectionTestController::class)
                ->middleware([
                    'permission:admin.mail.test_connections',
                    'mail.audit:antivirus_connection_tested',
                ])
                ->name('antivirus.test');

            Route::get('/mailboxes', [MailboxController::class, 'index'])
                ->middleware('permission:admin.mail.view|admin.mail.manage_mailboxes')
                ->name('mailboxes.index');

            Route::post('/mailboxes', [MailboxController::class, 'store'])
                ->middleware([
                    'permission:admin.mail.manage_mailboxes',
                    'mail.audit:mailbox_created',
                ])
                ->name('mailboxes.store');

            Route::get('/mailboxes/{mailbox}', [MailboxController::class, 'show'])
                ->middleware('permission:admin.mail.view|admin.mail.manage_mailboxes')
                ->name('mailboxes.show');

            Route::put('/mailboxes/{mailbox}', [MailboxController::class, 'update'])
                ->middleware([
                    'permission:admin.mail.manage_mailboxes',
                    'mail.audit:mailbox_updated',
                ])
                ->name('mailboxes.update');

            Route::delete('/mailboxes/{mailbox}', [MailboxController::class, 'destroy'])
                ->middleware([
                    'permission:admin.mail.manage_mailboxes',
                    'mail.audit:mailbox_deleted',
                ])
                ->name('mailboxes.destroy');

            Route::get(
                '/mailboxes/{mailbox}/channels',
                [MailboxChannelController::class, 'index']
            )
                ->middleware('permission:admin.mail.view|admin.mail.manage_channels')
                ->name('mailboxes.channels.index');

            Route::post(
                '/mailboxes/{mailbox}/channels',
                [MailboxChannelController::class, 'store']
            )
                ->middleware([
                    'permission:admin.mail.manage_channels',
                    'mail.audit:channel_created',
                ])
                ->name('mailboxes.channels.store');

            Route::get('/channels/{channel}', [MailboxChannelController::class, 'show'])
                ->middleware('permission:admin.mail.view|admin.mail.manage_channels')
                ->name('channels.show');

            Route::put('/channels/{channel}', [MailboxChannelController::class, 'update'])
                ->middleware([
                    'permission:admin.mail.manage_channels',
                    'mail.audit:channel_updated',
                ])
                ->name('channels.update');

            Route::delete('/channels/{channel}', [MailboxChannelController::class, 'destroy'])
                ->middleware([
                    'permission:admin.mail.manage_channels',
                    'mail.audit:channel_deleted',
                ])
                ->name('channels.destroy');

            Route::get(
                '/provider-connections',
                [MailProviderConnectionController::class, 'index']
            )
                ->middleware('permission:admin.mail.view|admin.mail.manage_provider_connections')
                ->name('provider-connections.index');

            Route::post('/provider-connections', [MailProviderConnectionController::class, 'store'])
                ->middleware([
                    'permission:admin.mail.manage_provider_connections',
                    'mail.audit:provider_connection_created',
                ])
                ->name('provider-connections.store');

            Route::get(
                '/provider-connections/{providerConnection}',
                [MailProviderConnectionController::class, 'show']
            )
                ->middleware('permission:admin.mail.view|admin.mail.manage_provider_connections')
                ->name('provider-connections.show');

            Route::put('/provider-connections/{providerConnection}', [MailProviderConnectionController::class, 'update'])
                ->middleware([
                    'permission:admin.mail.manage_provider_connections',
                    'mail.audit:provider_connection_updated',
                ])
                ->name('provider-connections.update');

            Route::delete('/provider-connections/{providerConnection}', [MailProviderConnectionController::class, 'destroy'])
                ->middleware([
                    'permission:admin.mail.manage_provider_connections',
                    'mail.audit:provider_connection_deleted',
                ])
                ->name('provider-connections.destroy');

            Route::prefix('/oauth-integrations')->name('oauth-integrations.')->group(function (): void {
                Route::get('/callback', MailOAuthCallbackController::class)
                    ->middleware('permission:admin.mail.connect_oauth_accounts|admin.mail.manage_oauth_integrations')->name('callback');
                Route::get('/', [MailOAuthIntegrationController::class, 'index'])
                    ->middleware('permission:admin.mail.view_oauth_integrations|admin.mail.manage_oauth_integrations')
                    ->name('index');
                Route::get('/create', [MailOAuthIntegrationController::class, 'create'])
                    ->middleware('permission:admin.mail.manage_oauth_integrations')->name('create');
                Route::post('/', [MailOAuthIntegrationController::class, 'store'])
                    ->middleware(['permission:admin.mail.manage_oauth_integrations', 'mail.audit:oauth_integration_created'])->name('store');
                Route::get('/{connection}/edit', [MailOAuthIntegrationController::class, 'edit'])
                    ->middleware('permission:admin.mail.view_oauth_integrations|admin.mail.manage_oauth_integrations')->whereNumber('connection')->name('edit');
                Route::put('/{connection}', [MailOAuthIntegrationController::class, 'update'])
                    ->middleware(['permission:admin.mail.manage_oauth_integrations', 'mail.audit:oauth_integration_updated'])->whereNumber('connection')->name('update');
                Route::delete('/{connection}', [MailOAuthIntegrationController::class, 'destroy'])
                    ->middleware(['permission:admin.mail.manage_oauth_integrations', 'mail.audit:oauth_integration_deleted'])->whereNumber('connection')->name('destroy');
                Route::post('/{connection}/restore', [MailOAuthIntegrationController::class, 'restore'])
                    ->middleware(['permission:admin.mail.manage_oauth_integrations', 'mail.audit:oauth_integration_restored'])->whereNumber('connection')->name('restore');
                Route::delete('/{connection}/force', [MailOAuthIntegrationController::class, 'forceDestroy'])
                    ->middleware(['permission:admin.mail.manage_oauth_integrations', 'mail.audit:oauth_integration_force_deleted'])->whereNumber('connection')->name('force-destroy');
                Route::get('/{connection}/authorize', MailOAuthAuthorizationController::class)
                    ->middleware(['permission:admin.mail.connect_oauth_accounts|admin.mail.manage_oauth_integrations', 'mail.audit:oauth_authorization_started'])->whereNumber('connection')->name('authorize');
                Route::post('/{connection}/refresh', MailOAuthRefreshController::class)
                    ->middleware(['permission:admin.mail.connect_oauth_accounts|admin.mail.manage_oauth_integrations', 'mail.audit:oauth_token_refreshed'])->whereNumber('connection')->name('refresh');
                Route::post('/{connection}/test', MailOAuthConnectionTestController::class)
                    ->middleware(['permission:admin.mail.test_connections', 'mail.audit:oauth_connection_tested'])->whereNumber('connection')->name('test');
                Route::post('/{connection}/disconnect', MailOAuthDisconnectController::class)
                    ->middleware(['permission:admin.mail.connect_oauth_accounts|admin.mail.manage_oauth_integrations', 'mail.audit:oauth_account_disconnected'])->whereNumber('connection')->name('disconnect');
            });
        });
    });

    Route::post('/logout', function (Request $request) {
        auth()->guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    })->name('logout');
});
