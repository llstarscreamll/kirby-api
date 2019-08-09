<?php

use llstarscreamll\Novelties\UI\API\V1\Controllers\NoveltiesController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->apiResource('novelties', NoveltiesController::class);
    });
