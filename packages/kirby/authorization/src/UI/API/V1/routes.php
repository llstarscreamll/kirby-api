<?php

use Illuminate\Support\Facades\Route;
use Kirby\Authorization\UI\API\V1\Controllers\RolesController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->get('roles', RolesController::class);
    });
