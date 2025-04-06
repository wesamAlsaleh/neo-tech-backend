<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use App\Models\wishlist;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WishlistController extends Controller
{
    /**
     * Display a listing of the wishlist of the authenticated user.
     */
    public function index(Request $request)
    {
        try {
            // Get the authenticated user id
            $userData = $request->user();

            // Get the user from the database using the authenticated user id
            $user = User::find($userData->id);

            // If the user is not found, return an error
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                    'devMessage' => 'USER_NOT_FOUND'
                ], 404);
            }

            // Get the user's wishlist with product details
            $wishlist = Wishlist::where('user_id', $user->id)
                ->with('product') // Load related product data
                ->get();

            return response()->json([
                'message' =>  "$user->first_name's wishlist",
                // "userWishlist" => $wishlist, // Wishlist with product details
                'products' => $wishlist->pluck('product'), // Extract only the product details
                'productCount' => $wishlist->pluck('product')->count() // Count of products in the wishlist
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a new wishlist item in storage. (Add a product to the wishlist)
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'product_id' => 'required|exists:products,id'
            ]);

            // Get the authenticated user id
            $userData = $request->user();

            // Get the user from the database using the authenticated user id
            $user = User::find($userData->id);

            // If the user is not found, return an error
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                    'devMessage' => 'USER_NOT_FOUND'
                ], 404);
            }

            // Check if the product exists
            $product = Product::find($request->product_id);

            // If the product is not found, return an error
            if (!$product) {
                return response()->json([
                    'message' => 'Product not found',
                    'devMessage' => 'PRODUCT_NOT_FOUND'
                ], 404);
            }

            // Check if the product is already in the user's wishlist
            $existingWishlistItem = wishlist::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->first();

            // If the product is already in the wishlist, return an error
            if ($existingWishlistItem) {
                return response()->json([
                    'message' => "$product->product_name is already in your wishlist",
                    'devMessage' => 'PRODUCT_ALREADY_IN_WISHLIST'
                ], 409);
            }

            // Add the product to the user's wishlist
            Wishlist::create([
                'user_id' => $user->id,
                'product_id' => $product->id
            ]);

            return response()->json([
                'message' => "$product->product_name has been added to your wishlist",
                'wishlist_items_count' => $user->wishlist()->count(),
            ], 201);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified wishlist item from storage. (Remove a product from the wishlist by product id)
     */
    public function removeWishlistProduct(Request $request, string $id)
    {
        try {
            // Get the authenticated user id
            $userData = $request->user();

            // Get the user from the database using the authenticated user id
            $user = User::find($userData->id);

            // If the user is not found, return an error
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                    'devMessage' => 'USER_NOT_FOUND'
                ], 404);
            }

            // Get the product from the database using the product id
            $product = Product::find($id);

            // If the product is not found, return an error
            if (!$product) {
                return response()->json([
                    'message' => 'Product not found',
                    'devMessage' => 'PRODUCT_NOT_FOUND'
                ], 404);
            }

            // Get the wishlist item from the database using the user id and product id
            $wishlistItem = Wishlist::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->first();

            // If the wishlist product is not found, return an error
            if (!$wishlistItem) {
                return response()->json([
                    'message' => 'Wishlist item not found',
                    'devMessage' => 'WISHLIST_ITEM_NOT_FOUND'
                ], 404);
            }

            // Delete the wishlist item
            $wishlistItem->delete();

            return response()->json([
                'message' => "$product->product_name has been removed from your wishlist",
                'wishlist_items_count' => $user->wishlist()->count(),
            ], 200);
        } catch (\Exception $e) {
        }
    }

    /**
     * Remove the specified wishlist item from storage. (Remove a product from the wishlist by wishlist id)
     */
    public function destroy(string $id)
    {
        try {
            // Get the wishlist item from the database using the id (user_id and product_id)
            $wishlistItem = Wishlist::find($id);

            // If the wishlist product is not found, return an error
            if (!$wishlistItem) {
                return response()->json([
                    'message' => 'Wishlist item not found',
                    'devMessage' => 'WISHLIST_ITEM_NOT_FOUND'
                ], 404);
            }

            // Get the product from the wishlist item
            $product = $wishlistItem->product;

            // Delete the wishlist item
            $wishlistItem->delete();

            return response()->json([
                'message' => "$product->product_name has been removed from your wishlist",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }
}
