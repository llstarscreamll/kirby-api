<?php

use Illuminate\Support\Facades\Route;
use Kirby\Production\UI\API\V1\Controllers\ProductionLogsController;
use Kirby\Production\UI\API\V1\Controllers\ProductionReportsController;
use Kirby\Production\UI\API\V1\Controllers\ExportProductionLogsToCsvController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($router) {
        $router->post('production-logs/export-to-csv', ExportProductionLogsToCsvController::class);
        $router->apiResource('production-logs', ProductionLogsController::class);
        $router->get('production-reports', ProductionReportsController::class);
    });
