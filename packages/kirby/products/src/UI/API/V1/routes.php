<?php

use Illuminate\Support\Facades\Route;
use Kirby\Products\UI\API\V1\Controllers\ProductsController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($router) {
        $router->apiResource('products', ProductsController::class)->only(['index']);
    });
