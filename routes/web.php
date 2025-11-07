<?php

use App\Enums\TicketStatus;
use App\Http\Controllers\Agent\DashboardController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::get('/', [IndexController::class, 'index'])->name('index');

Route::get('locale/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'ru'])) {
        Session::put('locale', $locale);
    }

    return redirect()->back();
})->name('locale.switch');

Route::middleware('auth')->group(function () {
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');
    Route::post('/tickets', [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}/edit', [TicketController::class, 'edit'])->name('tickets.edit');
    Route::put('/tickets/{ticket}', [TicketController::class, 'update'])->name('tickets.update');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->whereNumber('ticket')->name('tickets.show');
    Route::get('/tickets/{category}', [TicketController::class, 'index'])->name('tickets.index');
    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');

    Route::resource('tickets.replies', ReplyController::class)
        ->only(['store', 'update', 'destroy'])
        ->shallow();

    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::post('avatar', [ProfileController::class, 'updateAvatar'])->name('avatar.update');
        Route::post('/2fa/enable', [ProfileController::class, 'enableTwoFactor'])->name('2fa.enable');
        Route::post('/2fa/disable', [ProfileController::class, 'disableTwoFactor'])->name('2fa.disable');
    });
});

Route::prefix('panel')->middleware(['auth', 'admin-agent'])->name('panel.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/tickets/create', [TicketController::class, 'create'])->name('tickets.create');

    Route::middleware('admin')->resource('users', UserController::class);

    Route::delete('/tickets/{ticket}', [TicketController::class, 'destroy'])->name('tickets.destroy');

    $statuses = collect(TicketStatus::cases())->map(fn ($case) => strtolower($case->name))->all();
    $filters = ['my', 'unassigned', 'my-pending-approvals', 'trash'];
    $categories = array_merge($statuses, $filters);

    Route::get('/{category}', [TicketController::class, 'index'])
        ->whereIn('category', $categories)
        ->name('index');
});

Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('2fa.challenge');
Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->name('2fa.challenge.store');

require __DIR__.'/auth.php';
