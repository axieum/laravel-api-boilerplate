<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API versions for your application. These
| are loaded by the RouteServiceProvider within a group which is assigned
| the "api" middleware group. Enjoy building your API!
|
*/

// Version 01
Route::prefix('/v1')
    ->namespace('Api\v1')
    ->group(base_path('routes/api/v1/_index.php'));
