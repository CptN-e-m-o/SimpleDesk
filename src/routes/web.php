<?php

use App\Http\Controllers\Admin\AgentController;
use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\Mail\AttachmentAntivirusConnectionTestController;
use App\Http\Controllers\Admin\Mail\EmailAttachmentDownloadController;
use App\Http\Controllers\Admin\Mail\MailboxChannelController;
use App\Http\Controllers\Admin\Mail\MailboxController;
use App\Http\Controllers\Admin\Mail\MailboxChannelConnectionTestController;
use App\Http\Controllers\Admin\Mail\MailProviderConnectionController;
use App\Http\Controllers\Admin\Mail\MailProviderConnectionTestController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TeamController;
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
        Route::get('/dashboard', function () {
            return Inertia::render('Admin/Dashboard');
        })
            ->middleware('permission:admin.manage.manage_dashboard')
            ->name('dashboard');

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
            Route::post('/channels/{channel}/test', MailboxChannelConnectionTestController::class)
                ->middleware('permission:admin.mail.test_connections')
                ->name('channels.test');

            Route::post('/provider-connections/{providerConnection}/test', MailProviderConnectionTestController::class)
                ->middleware('permission:admin.mail.test_connections')
                ->name('provider-connections.test');

            Route::post('/antivirus/test', AttachmentAntivirusConnectionTestController::class)
                ->middleware('permission:admin.mail.test_connections')
                ->name('antivirus.test');

            Route::get('/mailboxes', [MailboxController::class, 'index'])
                ->middleware('permission:admin.mail.view|admin.mail.manage_mailboxes')
                ->name('mailboxes.index');

            Route::post('/mailboxes', [MailboxController::class, 'store'])
                ->middleware('permission:admin.mail.manage_mailboxes')
                ->name('mailboxes.store');

            Route::get('/mailboxes/{mailbox}', [MailboxController::class, 'show'])
                ->middleware('permission:admin.mail.view|admin.mail.manage_mailboxes')
                ->name('mailboxes.show');

            Route::put('/mailboxes/{mailbox}', [MailboxController::class, 'update'])
                ->middleware('permission:admin.mail.manage_mailboxes')
                ->name('mailboxes.update');

            Route::delete('/mailboxes/{mailbox}', [MailboxController::class, 'destroy'])
                ->middleware('permission:admin.mail.manage_mailboxes')
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
                ->middleware('permission:admin.mail.manage_channels')
                ->name('mailboxes.channels.store');

            Route::get('/channels/{channel}', [MailboxChannelController::class, 'show'])
                ->middleware('permission:admin.mail.view|admin.mail.manage_channels')
                ->name('channels.show');

            Route::put('/channels/{channel}', [MailboxChannelController::class, 'update'])
                ->middleware('permission:admin.mail.manage_channels')
                ->name('channels.update');

            Route::delete('/channels/{channel}', [MailboxChannelController::class, 'destroy'])
                ->middleware('permission:admin.mail.manage_channels')
                ->name('channels.destroy');

            Route::get(
                '/provider-connections',
                [MailProviderConnectionController::class, 'index']
            )
                ->middleware('permission:admin.mail.view|admin.mail.manage_provider_connections')
                ->name('provider-connections.index');

            Route::post(
                '/provider-connections',
                [MailProviderConnectionController::class, 'store']
            )
                ->middleware('permission:admin.mail.manage_provider_connections')
                ->name('provider-connections.store');

            Route::get(
                '/provider-connections/{providerConnection}',
                [MailProviderConnectionController::class, 'show']
            )
                ->middleware('permission:admin.mail.view|admin.mail.manage_provider_connections')
                ->name('provider-connections.show');

            Route::put(
                '/provider-connections/{providerConnection}',
                [MailProviderConnectionController::class, 'update']
            )
                ->middleware('permission:admin.mail.manage_provider_connections')
                ->name('provider-connections.update');

            Route::delete(
                '/provider-connections/{providerConnection}',
                [MailProviderConnectionController::class, 'destroy']
            )
                ->middleware('permission:admin.mail.manage_provider_connections')
                ->name('provider-connections.destroy');
        });
    });

    Route::post('/logout', function (Request $request) {
        auth()->guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    })->name('logout');
});
