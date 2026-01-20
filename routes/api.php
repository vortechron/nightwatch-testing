<?php

use Illuminate\Support\Facades\Route;
use Vortechron\NightwatchTesting\Http\Controllers\NightwatchTestController;

Route::prefix('api/nightwatch-test')->group(function () {
    // Public endpoint (no auth required)
    Route::get('/public', [NightwatchTestController::class, 'public'])
        ->name('nightwatch-test.public');

    // Protected endpoint (requires auth)
    Route::middleware('auth:web')->group(function () {
        Route::get('/authenticated', [NightwatchTestController::class, 'authenticated'])
            ->name('nightwatch-test.authenticated');
    });
});
