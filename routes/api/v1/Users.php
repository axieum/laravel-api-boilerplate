<?php

/*
|--------------------------------------------------------------------------
| API Routes - Version 01 - Users
|--------------------------------------------------------------------------
*/

Route::get('/', 'UserController@index');
Route::post('/', 'UserController@store');

Route::get('/me', 'UserController@current');

Route::prefix('/{user}')->group(function () {
    Route::get('/', 'UserController@show');
    Route::patch('/', 'UserController@update');
    Route::delete('/', 'UserController@destroy');

    // Notifications
    Route::prefix('/notifications')->group(function () {
        Route::get('/', 'UserNotificationController@index');
        Route::get('/{notification}', 'UserNotificationController@show');
        Route::patch('/{notification}', 'UserNotificationController@mark');
        Route::delete('/{notification}', 'UserNotificationController@destroy');
    });

    // Roles
    Route::prefix('/roles')->group(function () {
        Route::get('/', 'UserRoleController@index');
        Route::put('/{role}', 'UserRoleController@assign');
        Route::delete('/{role}', 'UserRoleController@retract');
    });

    // Abilities
    Route::prefix('/abilities')->group(function () {
        Route::get('/', 'UserAbilityController@index');
        Route::put('/{ability}', 'UserAbilityController@attach');
        Route::delete('/{ability}', 'UserAbilityController@detach');
    });
});
