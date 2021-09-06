<?php

use Illuminate\Support\Facades\Route;
use Kirby\Machines\UI\API\V1\Controllers\MachinesController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($router) {
        $router->apiResource('machines', MachinesController::class);
    });
