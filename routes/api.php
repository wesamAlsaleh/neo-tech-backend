<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


/**
 * Notes:
 * php artisan route:list --> to see all routes
 * http://127.0.0.1:8000//api/test --> to see the result of the route test (do not forget to run the server and the /api/ is the prefix of the route)
 */

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


Route::get('/test', function () {
    return 'Hello World';
});
