<?php

use App\Http\Controllers\RefillSpotController;
use App\Http\Middleware\EnsureUserIsStaff;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/admin/refill-spots');

Route::middleware(['auth', EnsureUserIsStaff::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('refill-spots', RefillSpotController::class);

        Route::post('refill-spots/{refill_spot}/restore', [RefillSpotController::class, 'restore'])
            ->name('refill-spots.restore')
            ->withTrashed();
    });
