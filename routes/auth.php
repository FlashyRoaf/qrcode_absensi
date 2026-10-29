<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\EmailVerificationNotificationController;
use App\Http\Controllers\Auth\EmailVerificationPromptController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\VerifyEmailController;
use App\Http\Controllers\PenaltyController;
use App\Http\Controllers\QrCodeController;
use Illuminate\Support\Facades\Route;

// No public registration: only admins create accounts, from
// Admin > User Management. The old /register routes and controller
// were removed on purpose, so a stranger cannot self-register and
// start recording attendance.
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::get('verify-email', EmailVerificationPromptController::class)
        ->name('verification.notice');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('verification.send');

    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');

    Route::get('scan', [QrCodeController::class, 'index'])
        ->middleware('scanner')->name('qrcode.scan');
        
    Route::post('scan', [QrCodeController::class, 'create'])
        ->middleware('scanner')->name('qrcode.create');

    Route::get('scan/status/{token}', [QrCodeController::class, 'status'])
        ->middleware('scanner')->name('qrcode.status');

    Route::match(['get', 'post'], 'scanned/{token}', [AttendanceController::class, 'index'])
        ->name('qrcode.scanned');
    
    Route::get('penalties', [PenaltyController::class, 'index'])->name('penalties');
    Route::post('penalties/{penalty}/upload', [PenaltyController::class, 'upload']);
    Route::get('penalties/{penalty}/proof', [PenaltyController::class, 'proof'])->name('penalties.proof');
});
