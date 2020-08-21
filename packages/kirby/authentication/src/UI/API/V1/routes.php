<?php

use Illuminate\Support\Facades\Route;
use Kirby\Authentication\UI\API\V1\Controllers\ApiAuthenticationController;

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

Route::prefix('api/v1/auth')
    ->middleware('api')
    ->group(function ($route) {
        $route->post('login', [ApiAuthenticationController::class, 'login']);
        $route->post('sign-up', [ApiAuthenticationController::class, 'signUp']);
        $route->delete('logout', [ApiAuthenticationController::class, 'logout'])->middleware('auth:api');
        $route->get('user', [ApiAuthenticationController::class, 'getAuthUser'])->middleware('auth:api');
    });
