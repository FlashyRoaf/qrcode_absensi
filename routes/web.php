<?php

use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\WeeklyReportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Route::get('dashboard', function () {
//     return Inertia::render('Dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/admin/users', [UserController::class, 'index'])->middleware('admin')->name('users');
    Route::post('/admin/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/admin/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/admin/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::get('/admin/weekly-report', [WeeklyReportController::class, 'show'])->middleware('admin')->name('weekly-reports');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
