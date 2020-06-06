<?php

use Illuminate\Support\Facades\Route;
use Kirby\Company\UI\API\V1\Controllers\CostCentersController;
use Kirby\Company\UI\API\V1\Controllers\SubCostCentersController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->get('cost-centers', CostCentersController::class.'@index');
        $route->get('sub-cost-centers', SubCostCentersController::class.'@index');
    });
