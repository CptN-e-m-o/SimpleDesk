<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\IndexController;
use App\Http\Controllers\ReplyController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

Route::get('/', [IndexController::class, 'index'])->name('index');

Route::get('/registration', [RegisteredUserController::class, 'index'])->name('registration');
Route::get('/authorization', [AuthenticatedSessionController::class, 'index'])->name('authorization');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::resource('users', UserController::class);
});

Route::middleware('auth')->group(function () {
    Route::resource('tickets', TicketController::class)->except([
        'destroy',
    ]);

    Route::resource('tickets.replies', ReplyController::class)
        ->only(['store', 'update', 'destroy'])
        ->shallow();
});

Route::middleware(['auth', 'admin-agent'])->group(function () {
    Route::resource('tickets', TicketController::class)->only([
        'destroy',
    ]);
});

Route::middleware(['web'])->group(function () {
    Route::get('locale/{locale}', function ($locale) {
        if (in_array($locale, ['en', 'ru'])) {
            Session::put('locale', $locale);
        }

        return redirect()->back();
    })->name('locale.switch');
});

// User profile
Route::middleware('auth')
    ->prefix('profile')
    ->name('profile.')
    ->group(function () {
        Route::get('/', [ProfileController::class, 'index'])->name('index');
        Route::put('/', [ProfileController::class, 'update'])->name('update');
        Route::post('avatar', [ProfileController::class, 'updateAvatar'])->name('avatar.update');
    });

// 2FA
Route::middleware('auth')->group(function () {
    Route::post('/profile/2fa/enable', [ProfileController::class, 'enableTwoFactor'])->name('profile.2fa.enable');
    Route::post('/profile/2fa/disable', [ProfileController::class, 'disableTwoFactor'])->name('profile.2fa.disable');
});

Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'create'])->name('2fa.challenge');
Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'store'])->name('2fa.challenge.store');

require __DIR__.'/auth.php';
