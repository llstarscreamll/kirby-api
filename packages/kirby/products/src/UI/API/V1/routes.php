<?php

use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Kirby\Products\UI\API\V1\Controllers\CategoriesController;

Route::prefix('api/v1')
    ->middleware(['api'])
    ->group(function (Router $route) {
        $route->resource('categories', CategoriesController::class);
    });
