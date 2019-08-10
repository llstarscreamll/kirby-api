<?php

use llstarscreamll\Novelties\UI\API\V1\Controllers\NoveltiesController;
use llstarscreamll\Novelties\UI\API\V1\Controllers\NoveltyTypesController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->apiResource('novelties', NoveltiesController::class);
    });

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->apiResource('novelty-types', NoveltyTypesController::class);
    });
