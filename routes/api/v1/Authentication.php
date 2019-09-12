<?php

/*
|--------------------------------------------------------------------------
| API Routes - Version 01 - Authentication
|--------------------------------------------------------------------------
*/

Route::post('/register', 'RegisterController@register');

// Password Reset
Route::prefix('/password')->group(function () {
    Route::post('/email', 'ForgotPasswordController@sendResetLinkEmail');
    Route::post('/reset', 'ResetPasswordController@reset');
});

// Email Verification
Route::prefix('/verify')->group(function () {
    Route::get('/{id}/{hash}', 'VerificationController@verify');
    Route::post('/resend', 'VerificationController@resend');
});
