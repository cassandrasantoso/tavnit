<?php

use App\Http\Controllers\LoginController;
use App\Http\Controllers\PartnerApplicationController;
use App\Http\Controllers\RefillSpotController;
use App\Http\Middleware\EnsureUserIsStaff;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/refill-spots');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', EnsureUserIsStaff::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('refill-spots', RefillSpotController::class)->except(['show']);

        Route::post('refill-spots/{refill_spot}/restore', [RefillSpotController::class, 'restore'])
            ->name('refill-spots.restore')
            ->withTrashed();

        // Review queue: list by state, and one application's details.
        Route::get('applications', [PartnerApplicationController::class, 'index'])
            ->name('applications.index');
        Route::get('applications/{application}', [PartnerApplicationController::class, 'show'])
            ->name('applications.show');
    });
