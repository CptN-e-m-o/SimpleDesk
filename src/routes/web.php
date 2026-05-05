<?php

use App\Http\Controllers\Admin\DepartmentController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TeamController;
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
    });

    Route::post('/logout', function (Request $request) {
        auth()->guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    })->name('logout');
});
