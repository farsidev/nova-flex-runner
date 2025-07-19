<?php

use Farsi\NovaFlexRunner\Http\Controllers\FlexRunnerController;
use Farsi\NovaFlexRunner\Http\Controllers\LogViewerController;
use Illuminate\Support\Facades\Route;

Route::prefix('nova-vendor/nova-flex-runner')
    ->middleware(['nova'])
    ->group(function () {
        // Flex Runner routes
        Route::prefix('api')->group(function () {
            Route::get('commands', [FlexRunnerController::class, 'index']);
            Route::post('execute', [FlexRunnerController::class, 'execute']);
            Route::post('validate-inputs', [FlexRunnerController::class, 'validateInputs']);
            Route::get('status/{log}', [FlexRunnerController::class, 'status']);

            // Log Viewer routes
            Route::prefix('logs')->group(function () {
                Route::get('/', [LogViewerController::class, 'index']);
                Route::get('stats', [LogViewerController::class, 'stats']);
                Route::get('filters', [LogViewerController::class, 'filters']);
                Route::get('{log}', [LogViewerController::class, 'show']);
                Route::get('{log}/download', [LogViewerController::class, 'download']);
            });
        });
    });