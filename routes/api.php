<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FlashSaleController;
use App\Http\Controllers\HomePageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopFeatureController;
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


// Client routes for getting categories
Route::get('/categories', [CategoryController::class, 'getAllCategories']); // get all categories "good"
Route::get('/category/{id}', [CategoryController::class, 'getCategoryById']); // get a single category "good"

/**
|----------------------------------------------------------------------------------------
|   C routes that are protected by sanctum middleware and EnsureUserIsAdmin middleware
|----------------------------------------------------------------------------------------
 */
// Admin routes that are protected by sanctum middleware (auth:sanctum) [require the user to be authenticated] and EnsureUserIsAdmin middleware [require the user to be an admin]
Route::middleware(['auth:sanctum', EnsureUserIsAdmin::class])->group(function () {
    // Create/Update/Delete Category routes
    Route::post('/admin/create-category', [CategoryController::class, 'createCategory']); // store category in database "good"
    Route::post('/admin/update-category/{id}', [CategoryController::class, 'updateCategoryById']); // update category by id "good"
    Route::delete('/admin/delete-category/{id}', [CategoryController::class, 'deleteCategoryById']); // delete category by id "good"
    Route::patch('/admin/toggle-category-status/{id}', [CategoryController::class, 'toggleCategoryStatusById']); // toggle category status by id "good"
});

/**
|----------------------------------------------------------------------------------------------
|   Products routes that are protected by sanctum middleware and EnsureUserIsAdmin middleware
|----------------------------------------------------------------------------------------------
 */
Route::middleware(['auth:sanctum', EnsureUserIsAdmin::class])->group(function () {
    Route::post('/admin/create-product', [ProductController::class, 'createProduct']); // create a new product "Good"
    Route::post('/admin/update-product/{id}', [ProductController::class, 'updateProductById']); // update a product by id "Good"
    Route::delete('/admin/delete-product/{id}', [ProductController::class, 'deleteProductById']); // soft delete a product by id "Good"
    Route::put('/admin/products/restore/{id}', [ProductController::class, 'restoreProductById']); // restore a soft deleted product by id "Good"
    Route::get('/admin/products/trashed', [ProductController::class, 'getDeletedProducts']); // get all trashed products "Good"
    Route::patch('/admin/toggle-product-status/{id}', [ProductController::class, 'toggleProductStatusById']); // toggle product status by id "Good"
    Route::patch('/admin/toggle-product-availability/{id}', [ProductController::class, 'toggleProductAvailabilityById']); // toggle product availability by id "Good"
    Route::post('/admin/toggle-product-sale/{id}', [ProductController::class, 'putProductOnSale']); // put a product on sale by id "Good"
    Route::post('/admin/toggle-product-sale-off/{id}', [ProductController::class, 'removeProductFromSale']); // put a product off sale by id "Good"
    Route::get('/admin/sale-products', [ProductController::class, 'getProductsOnSale']); // get all products "Good"
});

// Client routes for getting categories
Route::get('/products', [ProductController::class, 'getAllProducts']); // get all products "Good"
Route::get('/products/{id}', [ProductController::class, 'getProductById']); // get a single product "Good"
Route::get('/products-by-name/{product_name}', [ProductController::class, 'searchProductsByName']); // get all products by name "Good"
Route::get('/products-by-category/{category_name}', [ProductController::class, 'searchProductsByCategory']); // get all products by category "Good"
Route::get('/products-by-rating/{rating}', [ProductController::class, 'searchProductsByRating']); // get all products by rating "Good"
Route::get('/products-by-slug/{slug}', [ProductController::class, 'searchProductsBySlug']); // get all products by slug "Good"
Route::get('/products-by-price/{min_price}/{max_price}', [ProductController::class, 'searchProductsByPriceRange']); // get all products by price "I think it is good"
Route::get('/products-by-availability/{availability}', [ProductController::class, 'searchProductsByAvailability']); // get all products by availability "Good"
Route::get('/products-by-status/{status}', [ProductController::class, 'searchProductsByStatus']); // get all products by status "Good"
Route::get('/best-selling-products', [ProductController::class, 'getBestSellingProducts']); // get best selling products "Good"
Route::get('/latest-products', [ProductController::class, 'getLatestProducts']); // get latest products "Good"
Route::get('/explore-products', [ProductController::class, 'getExploreProducts']); // get random products "Good"

/**
|----------------------------------------------------------------------------------------------
|   Shop Features API routes that are protected by sanctum middleware and EnsureUserIsAdmin middleware
|----------------------------------------------------------------------------------------------
 */
Route::middleware(['auth:sanctum', EnsureUserIsAdmin::class])->group(function () {
    Route::get('/admin/features', [ShopFeatureController::class, 'index']); // get all shop features "Good"
    Route::post('/admin/create-feature', [ShopFeatureController::class, 'store']); // create a new shop feature "Good"
    Route::post('/admin/update-feature/{id}', [ShopFeatureController::class, 'update']); // update a shop feature by id "Good"
    Route::delete('/admin/delete-feature/{id}', [ShopFeatureController::class, 'destroy']); // delete a shop feature by id "Good"
    Route::patch('/admin/toggle-feature-status/{id}', [ShopFeatureController::class, 'toggleFeatureStatusById']); // toggle shop feature status by id "Good"
});

// Client routes for getting shop features
Route::get('/active-features', [ShopFeatureController::class, 'getActiveFeatures']); // get 3 active features "Good"

/**
|----------------------------------------------------------------------------------------------
|   Flash Sales API routes that are protected by sanctum middleware and EnsureUserIsAdmin middleware
|----------------------------------------------------------------------------------------------
 */
Route::middleware(['auth:sanctum', EnsureUserIsAdmin::class])->group(function () {
    Route::get('/admin/flash-sales', [FlashSaleController::class, 'index']); // get all flash sales ""
    Route::post('/admin/create-flash-sale', [FlashSaleController::class, 'store']); // create a new flash sale "Good"
    Route::get('/admin/flash-sale/{id}', [FlashSaleController::class, 'show']); // get a single flash sale by id ""
});
