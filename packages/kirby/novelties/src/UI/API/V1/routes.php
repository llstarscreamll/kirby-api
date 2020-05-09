<?php

use Illuminate\Support\Facades\Route;
use Kirby\Novelties\UI\API\V1\Controllers\CreateManyNoveltiesController;
use Kirby\Novelties\UI\API\V1\Controllers\CreateNoveltiesApprovalsByEmployeeAndDateRangeController;
use Kirby\Novelties\UI\API\V1\Controllers\DeleteNoveltiesApprovalsByEmployeeAndDateRangeController;
use Kirby\Novelties\UI\API\V1\Controllers\ExportNoveltiesController;
use Kirby\Novelties\UI\API\V1\Controllers\NoveltiesController;
use Kirby\Novelties\UI\API\V1\Controllers\NoveltyApprovalsController;
use Kirby\Novelties\UI\API\V1\Controllers\NoveltyTypesController;
use Kirby\Novelties\UI\API\V1\Controllers\ReportByEmployeeController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->post('novelties/export', ExportNoveltiesController::class);
        $route->post('novelties/approvals-by-employee-and-date-range', CreateNoveltiesApprovalsByEmployeeAndDateRangeController::class);
        $route->delete('novelties/approvals-by-employee-and-date-range', DeleteNoveltiesApprovalsByEmployeeAndDateRangeController::class);
        $route->apiResource('novelties', NoveltiesController::class);
        $route->post('novelties/create-many', CreateManyNoveltiesController::class);
        $route->resource('novelties.approvals', NoveltyApprovalsController::class)->only(['store', 'destroy']);
    });

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->apiResource('novelty-types', NoveltyTypesController::class);
    });
