<?php

use llstarscreamll\TimeClock\UI\API\Controllers\TimeClockLogsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::prefix('api/v1/')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->apiResource('time-clock-logs', TimeClockLogsController::class);
    });
