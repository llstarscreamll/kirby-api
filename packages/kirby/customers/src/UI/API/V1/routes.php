<?php

use Illuminate\Support\Facades\Route;
use Kirby\Customers\UI\API\V1\Controllers\CustomersController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($router) {
        $router->apiResource('customers', CustomersController::class);
    });
