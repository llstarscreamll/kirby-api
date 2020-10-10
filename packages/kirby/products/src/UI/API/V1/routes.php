<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Kirby\Products\UI\API\V1\Controllers\CategoriesController;
use Kirby\Products\UI\API\V1\Controllers\ProductsController;

Route::prefix('api/v1')
    ->middleware(['api'])
    ->group(function (Router $route) {
        $route->resource('products', ProductsController::class);
        $route->resource('categories', CategoriesController::class);
    });
