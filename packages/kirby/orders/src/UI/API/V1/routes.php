<?php

use Illuminate\Support\Facades\Route;
use Kirby\Orders\UI\API\V1\Controllers\OrdersController;

Route::prefix('api/v1')
    ->middleware('auth:api')
    ->group(function ($route) {
        $route->resource('orders', OrdersController::class);
    });
