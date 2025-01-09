<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
| Notes:
| 1. php artisan route:list --> to see all routes
| 2. php artisan route:list --name=route_name --> to see the route with the name route_name
|
*/

// // Test the API
Route::get('/test', function () {
    return 'API is working good';
}); // working good


/**
|--------------------------------------------------------------------------
|   Auth routes that are not protected by sanctum middleware
|--------------------------------------------------------------------------
 */
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Auth routes that are protected by sanctum middleware (auth:sanctum)
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']); // get the authenticated user who is logged in
    Route::post('/logout', [AuthController::class, 'logout']); // logout the authenticated user who is logged in
});
