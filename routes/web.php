<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\FeedbackController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\TargetController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::get('login/siswa', [AuthController::class, 'showStudentLogin'])->name('login.siswa');
    Route::get('login/guru', [AuthController::class, 'showTeacherLogin'])->name('login.guru');
    Route::post('login', [AuthController::class, 'login'])->name('login.store');
    Route::post('login/student', [AuthController::class, 'studentLogin'])->name('login.student');
});

Route::post('logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function (): void {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::get('groups', [GroupController::class, 'index'])->name('groups.index');
    Route::post('groups', [GroupController::class, 'store'])->name('groups.store');
    Route::delete('groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
    Route::post('groups/{group}/students', [GroupController::class, 'storeStudent'])->name('groups.students.store');
    Route::delete('students/{student}', [GroupController::class, 'destroyStudent'])->name('groups.students.destroy');

    Route::resource('targets', TargetController::class)
        ->only(['index', 'store', 'destroy']);

    Route::resource('journals', JournalController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);

    Route::get('documentations/{documentation}', [DocumentationController::class, 'show'])
        ->name('documentations.show');

    Route::resource('feedback', FeedbackController::class)
        ->only(['index', 'store']);
});
