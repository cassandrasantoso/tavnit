<?php

use App\Http\Controllers\Api\RefillController;
use App\Http\Controllers\Api\SpotController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // 5 login attempts per minute.
    Route::post('login', [TokenController::class, 'store'])->middleware('throttle:5,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [TokenController::class, 'destroy']);

        // Who am I? Handy for checking a token works.
        Route::get('me', fn (Request $request) => $request->user()->only('id', 'name', 'email'));

        // Active refill spots, 20 per page.
        Route::get('spots', [SpotController::class, 'index']);

        // Send a refill: saved as pending; the same client_id twice counts once.
        Route::post('refills', [RefillController::class, 'store']);

        // The user's own refill history, one refill, and their stats.
        Route::get('refills', [RefillController::class, 'index']);
        Route::get('refills/{refill}', [RefillController::class, 'show']);
        Route::get('me/stats', [RefillController::class, 'stats']);
    });
});
