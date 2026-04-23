<?php

use App\Http\Controllers\Admin\DepartmentController;
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

    Route::middleware('role:admin,agent')->group(function () {
        Route::get('/dashboard', function () {
            return Inertia::render('Dashboard');
        })->name('dashboard');
        Route::prefix('agent')->name('agent.')->group(function () {
            Route::get('/tickets', function () {
                return Inertia::render('Tickets/Agent/Index');
            })->name('tickets');
        });
    });
    Route::middleware('role:admin')->group(function () {
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/dashboard', function () {
                return Inertia::render('Admin/Dashboard');
            })->name('dashboard');

            // Team routes
            Route::resource('teams', TeamController::class);
            Route::patch('/teams/{team}/restore', [TeamController::class, 'restore'])
                ->name('teams.restore')
                ->withTrashed();

            Route::delete('/teams/{team}/force-delete', [TeamController::class, 'forceDelete'])
                ->name('teams.force-delete')
                ->withTrashed();

            // Department routes
            Route::resource('departments', DepartmentController::class);
            Route::patch('/departments/{department}/restore', [DepartmentController::class, 'restore'])
                ->name('departments.restore')
                ->withTrashed();

            Route::delete('/departments/{department}/force-delete', [DepartmentController::class, 'forceDelete'])
                ->name('departments.force-delete')
                ->withTrashed();

        });
    });

    Route::post('/logout', function (Request $request) {
        auth()->guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    })->name('logout');
});
