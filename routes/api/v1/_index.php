<?php

/*
|--------------------------------------------------------------------------
| API Routes - Version 01
|--------------------------------------------------------------------------
|
| Here is where you can register API groups for your API version. These
| route groups are prefixed with the version identifier (e.g. "/v1").
|
*/

Route::prefix('/auth')->namespace('Auth')->group(__DIR__ . '/Authentication.php');
