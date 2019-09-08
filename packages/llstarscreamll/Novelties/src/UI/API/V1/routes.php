<?php

use llstarscreamll\Novelties\UI\API\V1\Controllers\NoveltiesController;
use llstarscreamll\Novelties\UI\API\V1\Controllers\NoveltyTypesController;
use llstarscreamll\Novelties\UI\API\V1\Controllers\NoveltyApprovalsController;
use llstarscreamll\Novelties\UI\API\V1\Controllers\CreateNoveltiesToUsersController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->apiResource('novelties', NoveltiesController::class);
        $route->post('novelties/create-novelties-to-users', CreateNoveltiesToUsersController::class);
        $route->resource('novelties.approvals', NoveltyApprovalsController::class)->only(['store', 'destroy']);
    });

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->apiResource('novelty-types', NoveltyTypesController::class);
    });
