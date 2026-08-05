<?php

use App\Http\Controllers\Api\AssistantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ReportController;
use Illuminate\Support\Facades\Route;

/*
 * Mobile API, consumed by the Flutter app (NovixHealth/nivaya-app).
 *
 * Token-authenticated via Sanctum rather than sessions. Every endpoint that
 * touches records goes through ScopesToCaller, so "may this caller see this
 * family member?" is answered in one place instead of per controller.
 */

// Sign-in is throttled by IP: it is the one unauthenticated endpoint here,
// and the obvious target for credential stuffing.
Route::post('/login', [AuthController::class, 'login'])
    ->middleware('throttle:6,1')
    ->name('api.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::get('/me', [AuthController::class, 'me'])->name('api.me');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('api.dashboard');
    Route::get('/members', [DashboardController::class, 'members'])->name('api.members');

    Route::get('/reports', [ReportController::class, 'index'])->name('api.reports');
    Route::post('/medications/{medication}/toggle-dose', [ReportController::class, 'toggleDose'])
        ->name('api.medications.toggle-dose');

    Route::get('/assistant', [AssistantController::class, 'history'])->name('api.assistant.history');

    // Every assistant message is a paid model call, so this one is capped
    // per user rather than left open — the admin AI usage page exists
    // precisely because there was no ceiling before.
    Route::post('/assistant/send', [AssistantController::class, 'send'])
        ->middleware('throttle:20,1')
        ->name('api.assistant.send');
});
