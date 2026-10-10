<?php

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
    });
});
