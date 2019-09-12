<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| URL Definitions
|--------------------------------------------------------------------------
|
| Here is where you can register route definitions for your application.
| These named routes may be used in, for example, email generation where
| the base url may differ to the api.
|
*/

// Email Verification
Route::get('/auth/verify/{id}/{hash}', 'Api\v1\Auth\VerificationController@verify')->name('verification.verify');

// Password Reset
Route::domain(config('frontend.url'))->get(config('frontend.password_url'))->name('password.reset');
