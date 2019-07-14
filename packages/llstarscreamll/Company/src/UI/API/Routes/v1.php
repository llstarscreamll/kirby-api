<?php

use llstarscreamll\Company\UI\API\Controllers\SubCostCentersController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->get('sub-cost-centers', SubCostCentersController::class.'@index');
    });
