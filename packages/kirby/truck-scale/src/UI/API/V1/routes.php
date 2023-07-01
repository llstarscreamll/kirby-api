<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Kirby\TruckScale\UI\API\V1\Controllers\DriversController;
use Kirby\TruckScale\UI\API\V1\Controllers\ClientsController;
use Kirby\TruckScale\UI\API\V1\Controllers\ExportWeighingsController;
use Kirby\TruckScale\UI\API\V1\Controllers\VehiclesController;
use Kirby\TruckScale\UI\API\V1\Controllers\WeighingsController;

Route::group(['prefix' => 'api/1.0', 'middleware' => 'auth:api'], function (Router $route) {
    $route->apiResource('vehicles', VehiclesController::class)->only(['index']);
    $route->apiResource('drivers', DriversController::class)->only(['index']);
    $route->apiResource('clients', ClientsController::class)->only(['index']);
    $route->post('weighings/export', ExportWeighingsController::class);
    $route->apiResource('weighings', WeighingsController::class)->only(['index', 'store', 'show', 'update']);
});
