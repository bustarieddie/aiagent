<?php

use App\Http\Controllers\AutomationController;
use App\Http\Controllers\BroadcastController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FlagController;
use App\Http\Controllers\LeadController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MediaController;
use App\Http\Controllers\PanelController;
use App\Http\Controllers\PatientController;
use App\Http\Controllers\StaffController;
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

    Route::get('/panels', [PanelController::class, 'index'])->name('panels');

    Route::get('/automation', [AutomationController::class, 'index'])->name('automation');
    Route::get('/broadcast', [BroadcastController::class, 'index'])->name('broadcast');
    Route::get('/staff', [StaffController::class, 'index'])->name('staff');

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
        Route::post('/leads/distribute', [LeadController::class, 'distribute']);
        Route::post('/leads/{phone}/classify', [LeadController::class, 'classifyOne'])->where('phone', '.*');
        Route::post('/leads/{phone}/assign', [LeadController::class, 'assign'])->where('phone', '.*');
        Route::patch('/leads/{phone}', [LeadController::class, 'update'])->where('phone', '.*');

        // Staff members
        Route::get('/staff', [StaffController::class, 'list']);
        Route::post('/staff', [StaffController::class, 'store']);
        Route::patch('/staff/{staff}', [StaffController::class, 'update']);
        Route::post('/staff/{staff}/toggle', [StaffController::class, 'toggle']);
        Route::delete('/staff/{staff}', [StaffController::class, 'destroy']);

        Route::get('/patients', [PatientController::class, 'list']);
        Route::get('/patients/{phone}', [PatientController::class, 'detail'])->where('phone', '.*');
        Route::patch('/patients/{phone}', [PatientController::class, 'update'])->where('phone', '.*');

        Route::get('/media/{phoneDir}/{filename}', [MediaController::class, 'show']);
        Route::delete('/media/{phoneDir}/{filename}', [MediaController::class, 'destroy']);

        // Automation rules
        Route::get('/automation', [AutomationController::class, 'list']);
        Route::post('/automation', [AutomationController::class, 'store']);
        Route::patch('/automation/{rule}', [AutomationController::class, 'update']);
        Route::post('/automation/{rule}/toggle', [AutomationController::class, 'toggle']);
        Route::post('/automation/{rule}/settings', [AutomationController::class, 'saveSettings']);
        Route::delete('/automation/{rule}', [AutomationController::class, 'destroy']);

        // Insurance / corporate panels
        Route::get('/panels', [PanelController::class, 'list']);
        Route::post('/panels', [PanelController::class, 'store']);
        Route::patch('/panels/{panel}', [PanelController::class, 'update']);
        Route::post('/panels/{panel}/toggle', [PanelController::class, 'toggle']);
        Route::delete('/panels/{panel}', [PanelController::class, 'destroy']);

        // Broadcast
        Route::get('/broadcasts/audience', [BroadcastController::class, 'audience']);
        Route::get('/broadcasts/history', [BroadcastController::class, 'history']);
        Route::post('/broadcasts', [BroadcastController::class, 'store']);
        Route::post('/broadcasts/{broadcast}/send-one', [BroadcastController::class, 'sendOne']);
        Route::post('/broadcasts/{broadcast}/finalize', [BroadcastController::class, 'finalize']);
        Route::post('/broadcasts/{broadcast}/cancel', [BroadcastController::class, 'cancel']);

        // Message templates (lokal)
        Route::get('/templates', [BroadcastController::class, 'templates']);
        Route::post('/templates', [BroadcastController::class, 'storeTemplate']);
        Route::patch('/templates/{template}', [BroadcastController::class, 'updateTemplate']);
        Route::delete('/templates/{template}', [BroadcastController::class, 'destroyTemplate']);
    });
});
