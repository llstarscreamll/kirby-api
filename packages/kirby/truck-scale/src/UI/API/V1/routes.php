<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Kirby\TruckScale\UI\API\V1\Controllers\DriversController;
use Kirby\TruckScale\UI\API\V1\Controllers\VehiclesController;

Route::group(['prefix' => 'api/1.0'], function (Router $route) {
    $route->apiResource('vehicles', VehiclesController::class)->only(['index']);
    $route->apiResource('drivers', DriversController::class)->only(['index']);
});
