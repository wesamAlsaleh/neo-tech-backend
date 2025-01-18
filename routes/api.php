<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
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

// Auth routes that are protected by sanctum middleware (auth:sanctum) [require the user to be authenticated]
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/user', [AuthController::class, 'user']); // get the authenticated user who is logged in
    Route::post('/logout', [AuthController::class, 'logout']); // logout the authenticated user who is logged in
    Route::get('/user-role', [AuthController::class, 'userRole']); // get the role of the authenticated user
});

// TODO: Add admin check middleware to the routes below to ensure only admin can access them
// Admin routes that are protected by sanctum middleware (auth:sanctum) [require the user to be authenticated]
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/admin/create-category', [CategoryController::class, 'createCategory']); // store category in database
    Route::get('/admin/categories', [CategoryController::class, 'getAllCategories']); // get all categories
    Route::get('/admin/category/{id}', [CategoryController::class, 'getCategoryById']); // get a single category
});
