<?php

use llstarscreamll\Employees\UI\API\Controllers\EmployeeApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->apiResource('employees', EmployeeApiController::class);
    });

Route::prefix('api/v1/')
    ->middleware(['api', 'auth:api'])
    ->post('employees/sync-by-csv-file', [
        'as' => 'sync_employees_by_csv_file',
        'uses' => EmployeeApiController::class.'@syncEmployeesByCsvFile',
    ]);
