<?php

use Kirby\TimeClock\UI\API\Controllers\CheckInController;
use Kirby\TimeClock\UI\API\Controllers\CheckOutController;
use Kirby\TimeClock\UI\API\Controllers\TimeClockLogApprovalsController;
use Kirby\TimeClock\UI\API\Controllers\TimeClockLogsController;

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
        $route->post('time-clock/check-in', CheckInController::class);
        $route->post('time-clock/check-out', CheckOutController::class);
        $route->apiResource('time-clock-logs', TimeClockLogsController::class);
        $route->resource('time-clock-logs.approvals', TimeClockLogApprovalsController::class)->only(['store', 'destroy']);
    });
