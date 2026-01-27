<?php

use Illuminate\Support\Facades\Route;
use Vortechron\NightwatchTesting\Http\Controllers\NightwatchTestController;

Route::prefix('api/nightwatch-test')->group(function () {
    // Public endpoint (no auth required)
    Route::get('/public', [NightwatchTestController::class, 'public'])
        ->name('nightwatch-test.public');

    // Outgoing request endpoint (no auth required)
    Route::get('/outgoing', [NightwatchTestController::class, 'outgoing'])
        ->name('nightwatch-test.outgoing');

    // Bulk entry generation (no auth required for testing convenience)
    Route::get('/bulk/{type}/{count}', [NightwatchTestController::class, 'bulk'])
        ->name('nightwatch-test.bulk')
        ->where('type', 'queries|cache|jobs|mail|notifications|exceptions|all')
        ->where('count', '[0-9]+');

    // Protected endpoint (requires auth)
    $guard = config('nightwatch-testing.detected_guard');
    logger()->info('[Nightwatch] Route guard from config: ' . ($guard ?? 'null'));

    if ($guard) {
        Route::middleware("auth:{$guard}")->group(function () {
            Route::get('/authenticated', [NightwatchTestController::class, 'authenticated'])
                ->name('nightwatch-test.authenticated');
            Route::post('/authenticated-job', [NightwatchTestController::class, 'dispatchAuthenticatedJob'])
                ->name('nightwatch-test.authenticated-job');
            Route::get('/authenticated-exception', [NightwatchTestController::class, 'authenticatedException'])
                ->name('nightwatch-test.authenticated-exception');
        });
    } else {
        logger()->warning('[Nightwatch] Authenticated route NOT registered - no guard detected');
    }
});
