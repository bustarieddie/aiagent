<?php

use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlagController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PatientController;
use Illuminate\Support\Facades\Route;

// Public — landing shortcut
Route::get('/', fn () => redirect('/admin/whatsapp-agent'));

// Login (no auth middleware) — email → OTP flow
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'requestOtp'])->name('login.request');
Route::get('/login/verify', [LoginController::class, 'showVerify'])->name('login.verify.show');
Route::post('/login/verify', [LoginController::class, 'verifyOtp'])->name('login.verify.submit');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Admin (all behind AdminAuth middleware)
Route::middleware('admin.auth')->prefix('admin/whatsapp-agent')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations');
    Route::get('/leads', [LeadController::class, 'index'])->name('leads');
    Route::get('/patients', [PatientController::class, 'index'])->name('patients');
    Route::get('/patients/{phone}', [PatientController::class, 'show'])
        ->where('phone', '.*')->name('patient.detail');

    // JSON API for the Blade pages' inline fetch calls
    Route::prefix('api')->group(function () {
        Route::get('/conversations', [ConversationController::class, 'list']);
        Route::post('/send', [ConversationController::class, 'send']);
        Route::delete('/conversations/{phone}', [ConversationController::class, 'destroy'])
            ->where('phone', '.*');

        Route::get('/flags/{phone}', [FlagController::class, 'show'])->where('phone', '.*');
        Route::patch('/flags/{phone}', [FlagController::class, 'update'])->where('phone', '.*');

        Route::get('/leads', [LeadController::class, 'list']);
        Route::get('/leads/export', [LeadController::class, 'export']);
        Route::get('/leads/classifiable', [LeadController::class, 'classifiable']);
        Route::post('/leads/{phone}/classify', [LeadController::class, 'classifyOne'])->where('phone', '.*');
        Route::patch('/leads/{phone}', [LeadController::class, 'update'])->where('phone', '.*');

        Route::get('/patients', [PatientController::class, 'list']);
        Route::get('/patients/{phone}', [PatientController::class, 'detail'])->where('phone', '.*');
        Route::patch('/patients/{phone}', [PatientController::class, 'update'])->where('phone', '.*');

        Route::get('/media/{phoneDir}/{filename}', [MediaController::class, 'show']);
        Route::delete('/media/{phoneDir}/{filename}', [MediaController::class, 'destroy']);
    });
});
