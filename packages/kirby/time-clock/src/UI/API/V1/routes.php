<?php

use Illuminate\Support\Facades\Route;
use Kirby\TimeClock\UI\API\V1\Controllers\CheckInController;
use Kirby\TimeClock\UI\API\V1\Controllers\CheckOutController;
use Kirby\TimeClock\UI\API\V1\Controllers\ExportLogsController;
use Kirby\TimeClock\UI\API\V1\Controllers\StatisticsController;
use Kirby\TimeClock\UI\API\V1\Controllers\TimeClockLogApprovalsController;
use Kirby\TimeClock\UI\API\V1\Controllers\TimeClockLogsController;

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
        $route->get('time-clock/statistics', StatisticsController::class);
        $route->get('time-clock-logs/export', ExportLogsController::class);
        $route->apiResource('time-clock-logs', TimeClockLogsController::class);
        $route->resource('time-clock-logs.approvals', TimeClockLogApprovalsController::class)->only(['store', 'destroy']);
    });
