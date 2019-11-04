<?php

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
    ->namespace('Kirby\Authentication\Http\Controllers')
    ->group(function ($route) {
        $route->post('login', 'ApiAuthenticationController@login');
        $route->post('sign-up', 'ApiAuthenticationController@signUp');
        $route->delete('logout', 'ApiAuthenticationController@logout')->middleware('auth:api');
        $route->get('user', 'ApiAuthenticationController@getAuthUser')->middleware('auth:api');
    });
