<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IdCardController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/switch/{familyMember}', [DashboardController::class, 'switch'])->name('dashboard.switch');

    Route::get('/id-card', [IdCardController::class, 'show'])->name('id-card.show');

    Route::get('/reports/upload', function () {
        request()->user()->ensureLinkedFamilyMember();

        return view('coming-soon', ['feature' => 'Report upload']);
    })->name('reports.upload');

    Route::get('/timeline', function () {
        request()->user()->ensureLinkedFamilyMember();

        return view('coming-soon', ['feature' => 'Health timeline']);
    })->name('timeline');

    Route::get('/share', function () {
        request()->user()->ensureLinkedFamilyMember();

        return view('coming-soon', ['feature' => 'Share a report']);
    })->name('share.index');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile/basic-info', [ProfileController::class, 'updateBasicInfo'])->name('profile.basic-info');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo');
    Route::post('/profile/address', [ProfileController::class, 'updateAddress'])->name('profile.address');
    Route::post('/profile/health', [ProfileController::class, 'updateHealth'])->name('profile.health');
    Route::post('/profile/emergency-contact', [ProfileController::class, 'updateEmergencyContact'])->name('profile.emergency-contact');
    Route::patch('/profile/theme', [ProfileController::class, 'updateTheme'])->name('profile.theme');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
