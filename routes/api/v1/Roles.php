<?php

/*
|--------------------------------------------------------------------------
| API Routes - Version 01 - Roles
|--------------------------------------------------------------------------
*/

Route::get('/', 'RoleController@index');
Route::post('/', 'RoleController@store');

Route::prefix('/{role}')->group(function () {
    Route::get('/', 'RoleController@show');
    Route::patch('/', 'RoleController@update');
    Route::delete('/', 'RoleController@destroy');

    // Abilities
    Route::prefix('/abilities')->group(function () {
        Route::get('/', 'RoleAbilityController@index');
        Route::put('/{ability}', 'RoleAbilityController@attach');
        Route::delete('/{ability}', 'RoleAbilityController@detach');
    });

    // Users
    Route::prefix('/users')->group(function () {
        Route::get('/', 'RoleUserController@index');
        Route::put('/{user}', 'RoleUserController@assign');
        Route::delete('/{user}', 'RoleUserController@retract');
    });
});
