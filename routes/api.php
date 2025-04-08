<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\FlashSaleController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ShopFeatureController;
use App\Http\Controllers\UserAddressController;
use App\Http\Controllers\WishlistController;
use App\Http\Middleware\EnsureUserIsAdmin;
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

/**
|----------------------------------------------------------------------------------------
|   Test the API Routes
|----------------------------------------------------------------------------------------
 */
Route::get('/test', function () {
    return 'API is working good';
}); // working good

/**
|----------------------------------------------------------------------------------------
|   Routes that are protected by sanctum middleware [require the user to be authenticated]
|----------------------------------------------------------------------------------------
 */
Route::middleware(['auth:sanctum'])->group(function () {
    // Auth routes for authenticated user
    Route::get('/user', [AuthController::class, 'user']); // get the authenticated user who is logged in "Good"
    Route::post('/logout', [AuthController::class, 'logout']); // logout the authenticated user who is logged in "Good"
    Route::get('/user-role', [AuthController::class, 'userRole']); // get the role of the authenticated user "Good"
    Route::post('/update-user', [AuthController::class, 'updateProfile']); // update the authenticated user "Good"
    Route::post('/update-password', [AuthController::class, 'changePassword']); // update the authenticated user password "Good"

    // Product routes for authenticated user
    Route::post('/put-rating/{id}', [ProductController::class, 'putRating']); // add a rating to a product "Good"

    // Wishlist routes for authenticated user
    Route::get('/wishlist-products', [WishlistController::class, 'index']); // get the authenticated user's wishlist "Good"
    Route::post('/add-wishlist-product', [WishlistController::class, 'store']); // add a product to the wishlist using the product id "Good"
    Route::delete('/clear-wishlist/{id}', [WishlistController::class, 'destroy']); // remove a product from the wishlist using the wishlist id "Good"
    Route::delete('/remove-wishlist-product/{id}', [WishlistController::class, 'removeWishlistProduct']); // remove a product from the wishlist using the product id "Good"
    Route::post('/move-to-cart', [WishlistController::class, 'moveToCart']); // move a product from the wishlist to the cart using the product id ""

    // Cart routes for authenticated user
    Route::get('/cart', [CartController::class, 'index']); // View cart items "Good"
    Route::post('/cart', [CartController::class, 'store']); // Add item to cart "Good"
    Route::post('/cart/{cartItemId}', [CartController::class, 'update']); // Update cart item quantity "Good"
    Route::delete('/cart/{cartItemId}', [CartController::class, 'destroy']); // Remove item from cart "Good"

    // User Address routes for authenticated user
    Route::post('/add-address', [UserAddressController::class, 'create']); // Create a new user address "Good"
    Route::post('/update-address', [UserAddressController::class, 'update']); // Update a user address "Good"

    // Order routes for authenticated user
    Route::post('/checkout', [OrderController::class, 'checkout']); // Checkout cart "Good"
    Route::get('/user-orders', [OrderController::class, 'getUserOrders']); // get all orders by user id "Good"
    Route::get('/order/{id}', [OrderController::class, 'getOrderById']); // get a single order by id "Good"
    Route::get('/order-details/{id}', [OrderController::class, 'getUserOrderDetails']); // get order details by order id ""
});

/**
|----------------------------------------------------------------------------------------
|   Routes that are protected by sanctum middleware and EnsureUserIsAdmin middleware [require the user to be an admin]
|----------------------------------------------------------------------------------------
 */
Route::middleware(['auth:sanctum', EnsureUserIsAdmin::class])->group(function () {
    // Category routes for admin
    Route::post('/admin/create-category', [CategoryController::class, 'createCategory']); // store category in database "good"
    Route::post('/admin/update-category/{id}', [CategoryController::class, 'updateCategoryById']); // update category by id "good"
    Route::delete('/admin/delete-category/{id}', [CategoryController::class, 'deleteCategoryById']); // delete category by id "good"
    Route::patch('/admin/toggle-category-status/{id}', [CategoryController::class, 'toggleCategoryStatusById']); // toggle category status by id "good"

    // Product routes for admin
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
    Route::post('/admin/remove-all-products-from-sale', [ProductController::class, 'removeAllProductsFromSale']); // remove all products from sale ""

    // Trust Badge routes for admin
    Route::get('/admin/features', [ShopFeatureController::class, 'index']); // get all shop features "Good"
    Route::post('/admin/create-feature', [ShopFeatureController::class, 'store']); // create a new shop feature "Good"
    Route::post('/admin/update-feature/{id}', [ShopFeatureController::class, 'update']); // update a shop feature by id "Good"
    Route::delete('/admin/delete-feature/{id}', [ShopFeatureController::class, 'destroy']); // delete a shop feature by id "Good"
    Route::patch('/admin/toggle-feature-status/{id}', [ShopFeatureController::class, 'toggleFeatureStatusById']); // toggle shop feature status by id "Good"

    // Flash Sale routes for admin
    Route::get('/admin/flash-sales', [FlashSaleController::class, 'index']); // get all flash sales "Good"
    Route::post('/admin/create-flash-sale', [FlashSaleController::class, 'store']); // create a new flash sale "Good"
    Route::get('/admin/flash-sale/{id}', [FlashSaleController::class, 'show']); // get a single flash sale by id "Good"
    Route::post('/admin/update-flash-sale/{id}', [FlashSaleController::class, 'update']); // update a flash sale by id "Good"
    Route::delete('/admin/delete-flash-sale/{id}', [FlashSaleController::class, 'destroy']); // delete a flash sale by id "Good"

    // Image Slider routes for admin
    Route::get('/admin/images', [ImageController::class, 'index']); // get all images "Good"
    Route::post('/admin/upload-image', [ImageController::class, 'store']); // upload an image "Good"
    Route::post('/admin/update-image/{id}', [ImageController::class, 'update']); // update an image by id "Good"
    Route::delete('/admin/delete-image/{id}', [ImageController::class, 'destroy']); // delete an image by id "Good"
    Route::patch('/admin/toggle-image-status/{id}', [ImageController::class, 'toggleImageActivity']); // toggle image status by id "Good"
    Route::patch('/admin/toggle-image-visibility/{id}', [ImageController::class, 'toggleImageVisibility']); // toggle image visibility by id "Good"

    // User Address routes for admin
    Route::delete('/delete-address', [UserAddressController::class, 'destroy']); // delete a user address "Good"

    // Cart routes for admin
    // TODO: add cart routes for admin

    // Order routes for admin
    Route::get('/admin/orders', [OrderController::class, 'index']); // get all orders "Good"
    Route::put('/admin/pending-order/{id}', [OrderController::class, 'setOrderStatusToPending']); // update order status by id to pending "Good"
    Route::put('/admin/completed-order/{id}', [OrderController::class, 'setOrderStatusToCompleted']); // update order status by id to completed "Good"
    Route::put('/admin/canceled-order/{id}', [OrderController::class, 'setOrderStatusToCanceled']); // update order status by id to canceled "Good"
    Route::get('/admin/order/{id}', [OrderController::class, 'show']); // get a single order by id "Good"
    Route::get('/admin/user-orders/{userId}', [OrderController::class, 'getOrdersByUserId']); // get all orders by user id "Good"
    Route::get('/admin/orders-by-status/{status}', [OrderController::class, 'getOrdersByStatus']); // get all orders by status ""

});

/**
|----------------------------------------------------------------------------------------
|   Routes that are not protected by any middleware [open to the public]
|----------------------------------------------------------------------------------------
 */
// Auth routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
// Route::post('/forgot-password', [AuthController::class, 'forgotPassword']); // send a password reset link to the user's email ""
// Route::post('/reset-password', [AuthController::class, 'resetPassword']); // reset the user's password ""

// Category routes
Route::get('/categories', [CategoryController::class, 'getAllCategories']); // get all categories "good"
Route::get('/category/{id}', [CategoryController::class, 'getCategoryById']); // get a single category "good"

// Product routes
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

// Trust Badge routes
Route::get('/active-features', [ShopFeatureController::class, 'getActiveFeatures']); // get the 3 active features "Good"

// Flash Sale routes
Route::get('/display-active-flash-sale', [FlashSaleController::class, 'display']); // get the active flash sale "Good"

// Image Slider routes
Route::get('/display-slider-images', [ImageController::class, 'display']); // get the active images "Good"
