<?php

use Kirby\Novelties\UI\API\V1\Controllers\CreateManyNoveltiesController;
use Kirby\Novelties\UI\API\V1\Controllers\NoveltiesController;
use Kirby\Novelties\UI\API\V1\Controllers\NoveltyApprovalsController;
use Kirby\Novelties\UI\API\V1\Controllers\NoveltyTypesController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->apiResource('novelties', NoveltiesController::class);
        $route->post('novelties/create-many', CreateManyNoveltiesController::class);
        $route->resource('novelties.approvals', NoveltyApprovalsController::class)->only(['store', 'destroy']);
    });

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->apiResource('novelty-types', NoveltyTypesController::class);
    });
