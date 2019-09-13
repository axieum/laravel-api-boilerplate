<?php

/*
|--------------------------------------------------------------------------
| API Routes - Version 01 - Abilities
|--------------------------------------------------------------------------
*/

Route::get('/', 'AbilityController@index');

Route::prefix('/{ability}')->group(function () {
    Route::get('/', 'AbilityController@show');

    Route::get('/roles', 'AbilityRoleController@index');
    Route::get('/users', 'AbilityUserController@index');
});
