<?php

use App\Http\Controllers\Admin\PenaltyManagementController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\WeeklyReportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PenaltyController;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->middleware('guest')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('admin')->prefix('admin')->group(function () {

        Route::get('users', [UserController::class, 'index'])->name('users');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
        Route::post('users/{user}/reset-device', [UserController::class, 'resetDevice'])
            ->name('users.reset-device');

        Route::get('attendance', [AttendanceController::class, 'show'])->name('attendance');
        Route::get('weekly-report', [WeeklyReportController::class, 'show'])->name('weekly-reports');
        Route::get('weekly-report/export', [WeeklyReportController::class, 'export'])->name('admin.weekly-report.export');

        Route::get('penalties', [PenaltyController::class, 'adminIndex'])->name('admin.penalties');
        Route::post('penalties/{penalty}/approve', [PenaltyController::class, 'approve']);
        Route::post('penalties/{penalty}/reject', [PenaltyController::class, 'reject']);
        Route::post('penalty-exempt-weeks', [PenaltyController::class, 'addExemptWeek']);
        Route::delete('penalty-exempt-weeks/{week}', [PenaltyController::class, 'deleteExemptWeek']);
    });
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
