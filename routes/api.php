<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/**
 * Notes:
 * php artisan route:list --> to see all routes
 * http://127.0.0.1:8000//api/test --> to see the result of the route test (do not forget to run the server and the /api/ is the prefix of the route)
 */

Route::get('/test', function () {
    return 'Hello World';
});

// Auth routes that are not protected by sanctum middleware
Route::post('/register', 'AuthController@register');
Route::post('/login', 'AuthController@login');

// Auth routes that are protected by sanctum middleware (auth:sanctum)
Route::middleware(['auth:sanctum'])->group(function () {});
