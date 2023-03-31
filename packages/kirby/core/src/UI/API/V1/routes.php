<?php

use Illuminate\Support\Facades\Route;
use Kirby\Core\UI\API\V1\Controllers\DownloadFileController;
use Kirby\Core\UI\API\V1\Controllers\UploadFileController;

Route::prefix('api/v1')
    ->middleware(['api', 'auth:api'])
    ->group(function ($route) {
        $route->post('files', UploadFileController::class);
        $route->get('files/{fileName}', DownloadFileController::class);
    });
