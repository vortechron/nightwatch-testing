<?php

use Illuminate\Support\Facades\Route;
use Vortechron\NightwatchTesting\Http\Controllers\NightwatchTestController;

Route::prefix('api/nightwatch-test')->group(function () {
    // Public endpoint (no auth required)
    Route::get('/public', [NightwatchTestController::class, 'public'])
        ->name('nightwatch-test.public');

    // Protected endpoint (requires auth)
    $guard = config('nightwatch-testing.detected_guard');
    logger()->info('[Nightwatch] Route guard from config: ' . ($guard ?? 'null'));

    if ($guard) {
        Route::middleware("auth:{$guard}")->group(function () {
            Route::get('/authenticated', [NightwatchTestController::class, 'authenticated'])
                ->name('nightwatch-test.authenticated');
        });
    } else {
        logger()->warning('[Nightwatch] Authenticated route NOT registered - no guard detected');
    }
});
