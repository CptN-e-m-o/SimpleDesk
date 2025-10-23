<?php

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\IndexController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IndexController::class, 'index'])->name('index');

Route::get('/registration', [RegisteredUserController::class, 'index'])->name('registration');

require __DIR__.'/auth.php';
