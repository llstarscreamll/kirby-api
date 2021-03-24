<?php

use Illuminate\Support\Facades\Route;
use Kirby\WorkShifts\UI\API\V1\Controllers\WorkShiftController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->apiResource('work-shifts', WorkShiftController::class);
    });
