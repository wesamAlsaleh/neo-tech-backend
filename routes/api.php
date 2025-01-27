<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Models\Product;
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


/**
|--------------------------------------------------------------------------
|   C routes that are not protected by sanctum middleware
|--------------------------------------------------------------------------
 */
// Client routes for getting categories
Route::get('/categories', [CategoryController::class, 'getAllCategories']); // get all categories "good"
Route::get('/category/{id}', [CategoryController::class, 'getCategoryById']); // get a single category "good"

// Admin routes that are protected by sanctum middleware (auth:sanctum) [require the user to be authenticated] and EnsureUserIsAdmin middleware [require the user to be an admin]
Route::middleware(['auth:sanctum', EnsureUserIsAdmin::class])->group(function () {
    // Create/Update/Delete Category routes
    Route::post('/admin/create-category', [CategoryController::class, 'createCategory']); // store category in database "good"
    Route::post('/admin/update-category/{id}', [CategoryController::class, 'updateCategoryById']); // update category by id "good"
    Route::delete('/admin/delete-category/{id}', [CategoryController::class, 'deleteCategoryById']); // delete category by id "good"
    Route::patch('/admin/toggle-category-status/{id}', [CategoryController::class, 'toggleCategoryStatusById']); // toggle category status by id "good"
});

/**
|--------------------------------------------------------------------------
|   Products routes that are not protected by sanctum middleware and EnsureUserIsAdmin middleware
|--------------------------------------------------------------------------
 */
Route::middleware(['auth:sanctum', EnsureUserIsAdmin::class])->group(function () {
    Route::post('/admin/create-product', [ProductController::class, 'createProduct']); // create a new product ""
});

// Client routes for getting categories
Route::get('/products', [ProductController::class, 'getAllProducts']); // get all products "good"
