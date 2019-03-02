<?php

use llstarscreamll\WorkShifts\UI\API\Controllers\WorkShiftController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->apiResource('work-shifts', WorkShiftController::class);
    });
