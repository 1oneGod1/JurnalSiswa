<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\TargetController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('targets', TargetController::class)
        ->only(['index', 'store', 'destroy']);

    Route::resource('journals', JournalController::class)
        ->only(['index', 'create', 'store', 'show']);

    Route::resource('feedback', FeedbackController::class)
        ->only(['index', 'store']);
});
