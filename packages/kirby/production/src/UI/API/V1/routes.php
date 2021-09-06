<?php

use Illuminate\Support\Facades\Route;
use Kirby\Production\UI\API\V1\Controllers\ExportProductionLogsToCsvController;
use Kirby\Production\UI\API\V1\Controllers\ProductionLogsController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($router) {
        $router->post('production-logs/export-to-csv', ExportProductionLogsToCsvController::class);
        $router->apiResource('production-logs', ProductionLogsController::class);
    });
