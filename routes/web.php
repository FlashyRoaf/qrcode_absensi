<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\WeeklyReportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;


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
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';