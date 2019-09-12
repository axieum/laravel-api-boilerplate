<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Frontend Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your frontend application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('FRONTEND_URL', 'http://localhost'),

    'password_url' => env('FRONTEND_PASSWORD_URL', '/password/reset/{token}'),

];
