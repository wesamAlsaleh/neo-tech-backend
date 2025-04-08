<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    /**
     * Display the user's cart.
     */
    public function index()
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Check if the user is authenticated
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized',
                    'devMessage' => 'USER_NOT_AUTHENTICATED',
                ], 401);
            }

            // Retrieve the user's cart items
            $cartItems = CartItem::where('user_id', $user->id)->get();

            // Filter out inactive products in the cart
            $cartItems = $cartItems->filter(function ($product) {
                return Product::where('id', $product->product_id)->where('is_active', true)->exists();
            });

            // Filter out checked out products in the cart
            $cartItems = $cartItems->filter(function ($item) {
                return !$item->is_checked_out;
            });

            // Return a 204 No Content response if the cart is empty
            if ($cartItems->isEmpty()) {
                return response()->json([
                    'message' => "Your cart is empty",
                    'cart' => [],
                ], 202);
            }

            // Filter out inactive products in the cart
            $cartItems = $cartItems->filter(function ($product) {
                return Product::where('id', $product->product_id)->where('is_active', true)->exists();
            });

            // Calculate the total price of the user's cart
            $totalPrice = $cartItems->sum(function ($productInCart) {
                return $productInCart->price * $productInCart->quantity;
            });

            // Fetch the product details for each cart item
            $cartItems = $cartItems->map(function ($cartItem) {
                // Get the product details
                $product = Product::find($cartItem->product_id);

                return [
                    'cart_item_id' => $cartItem->id,
                    'product' => $product,
                    'quantity' => $cartItem->quantity,
                    'unit_price' => $product->onSale ? $product->product_price_after_discount : $product->product_price,
                    'total_price' => $cartItem->price,
                ];
            });

            // Return the cart items
            return response()->json([
                'message' => 'Cart retrieved successfully',
                'cart' => $cartItems,
                'total_items' => $cartItems->count(),
                "total_price" => $totalPrice,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving the cart',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add an item to the cart.
     */
    public function store(Request $request)
    {
        try {
            // Get the user who made the request
            $userFromRequest = Auth::user();

            // Get the authenticated user from the database
            $user = User::find($userFromRequest->id);

            // Check if the user is authenticated
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized',
                    'devMessage' => 'USER_NOT_AUTHENTICATED',
                ], 401);
            }

            // Validate the request
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
            ]);

            // Get the quantity from the request
            $quantity = $request->input('quantity');

            // Get the product from the database or throw an error if not found
            $product = Product::find($request->product_id);

            // Check if the product does not exists
            if (!$product) {
                return response()->json([
                    'message' => 'Product not found',
                    'devMessage' => 'PRODUCT_NOT_FOUND',
                ], 404);
            }

            // Check if the product is in stock
            if ($product->product_stock < $quantity) {
                return response()->json([
                    'message' => 'Product out of stock',
                    'devMessage' => 'PRODUCT_OUT_OF_STOCK',
                ], 400);
            }

            // Check if the product is active
            if (!$product->is_active) {
                return response()->json([
                    'message' => 'Product is not available',
                    'devMessage' => 'PRODUCT_NOT_ACTIVE',
                ], 400);
            }

            // Get the product price
            $cartItemPrice = $product->onSale ? $product->product_price_after_discount : $product->product_price;

            // Check if item already exists in cart
            $cartItem = CartItem::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->first();

            // If the item already exists, increment the quantity
            if ($cartItem) {
                // Calculate new quantity
                $newQuantity = min($cartItem->quantity + $quantity, $product->product_stock); // Ensure it does not exceed stock eg. min(3 +   , 5) = 5, if stock is 5 it will be 5 max

                // Update with new quantity and price
                $cartItem->update([
                    'quantity' => $newQuantity, // Update the quantity
                    'price' => $cartItemPrice * $newQuantity,
                ]);

                $message = "$product->product_name has been updated in your cart";
            } else {
                // Otherwise, create a new cart item
                CartItem::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'price' => $cartItemPrice * $request->quantity,
                ]);

                $message = "$product->product_name has been added to your cart";
            }

            // After adding the item to the cart, update the count of items in the cart with the active only items
            $cartItems = CartItem::where('user_id', $user->id)->get();

            // Filter out inactive products in the cart
            $cartItemsCount = $cartItems->filter(function ($product) {
                return Product::where('id', $product->product_id)->where('is_active', true)->exists();
            })->count();

            return response()->json([
                'message' => $message,
                'cart_item' => [
                    'product_id' => $product->id,
                    'product_name' => $product->product_name,
                    'quantity' => $request->quantity,
                    'unit_price' => $cartItemPrice,
                    'total_price' => $cartItemPrice * $request->quantity,
                ],
                'total_items_in_user_cart' => $cartItemsCount,
            ]);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while adding the item to the cart',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update a cart item quantity.
     */
    public function update(Request $request, string $cartItemId)
    {
        try {
            // Get the user who made the request
            $userFromRequest = Auth::user();

            // Get the authenticated user from the database
            $user = User::find($userFromRequest->id);

            // Check if the user is authenticated
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized',
                    'devMessage' => 'USER_NOT_AUTHENTICATED',
                ], 401);
            }

            // Find the cart
            $cartItem = CartItem::Find($cartItemId);

            // Check if the cart item does not exist
            if (!$cartItem) {
                return response()->json([
                    'message' => 'Cart item not found',
                    'devMessage' => 'CART_ITEM_NOT_FOUND',
                ], 404);
            }

            // Validate the request
            $request->validate([
                'quantity' => 'required|integer|min:1',
            ]);

            // Get the quantity from the request
            $quantity = $request->input('quantity');

            // Find the product in the cart
            $product = Product::find($cartItem->product_id);

            // Check if the product does not exists
            if (!$product) {
                return response()->json([
                    'message' => 'Product not found',
                    'devMessage' => 'PRODUCT_NOT_FOUND',
                ], 404);
            }

            // Check if the product is active
            if (!$product->is_active) {
                return response()->json([
                    'message' => 'Product is not available',
                    'devMessage' => 'PRODUCT_NOT_ACTIVE',
                ], 400);
            }

            // Check if the product is in stock
            if ($product->product_stock < $request->quantity) {
                return response()->json([
                    'message' => 'Product out of stock',
                    'devMessage' => 'PRODUCT_OUT_OF_STOCK',
                ], 400);
            }

            // Original price of the product
            $cartItemPrice = $product->product_price;

            // If the product is on sale, update the price
            if ($product->onSale) {
                $cartItemPrice = $product->product_price_after_discount;
            }

            // Calculate new quantity
            $newQuantity = min($quantity, $product->product_stock); // Ensure it does not exceed stock eg. min(3 +   , 5) = 5, if stock is 5 it will be 5 max

            // Increment the quantity and update the price
            $cartItem->update([
                'quantity' => $newQuantity, // Update the quantity
                'price' => $cartItemPrice * $newQuantity, // Update the price
            ]);

            // After adding the item to the cart, update the count of items in the cart with the active only items
            $cartItems = CartItem::where('user_id', $user->id)->get();

            // Filter out inactive products in the cart
            $cartItemsCount = $cartItems->filter(function ($product) {
                return Product::where('id', $product->product_id)->where('is_active', true)->exists();
            })->count();

            return response()->json([
                'message' => "$user->first_name, your cart has been updated",
                'cart_item' => [
                    'product_id' => $cartItem->product_id,
                    'product_name' => $product->product_name,
                    'quantity' => $request->quantity,
                    'unit_price' => $cartItem->price,
                    'total_price' => $cartItem->price * $request->quantity,
                ],
                'total_items_in_user_cart' =>  $cartItemsCount,
            ]);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the cart',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove an item from the cart.
     */
    public function destroy(string $cartItemId)
    {
        try {
            // Get the user who made the request
            $userFromRequest = Auth::user();

            // Get the authenticated user from the database
            $user = User::find($userFromRequest->id);

            // Check if the user is authenticated
            if (!$user) {
                return response()->json([
                    'message' => 'Unauthorized',
                    'devMessage' => 'USER_NOT_AUTHENTICATED',
                ], 401);
            }

            // Find the cart
            $cartItem = CartItem::Find($cartItemId);

            // Check if the cart item does not exist
            if (!$cartItem) {
                return response()->json([
                    'message' => 'Cart item not found',
                    'devMessage' => 'CART_ITEM_NOT_FOUND',
                ], 404);
            }

            // Get the product from the cart item
            $product = Product::find($cartItem->product_id);

            // Check if the product does not exists
            if (!$product) {
                return response()->json([
                    'message' => 'Product not found',
                    'devMessage' => 'PRODUCT_NOT_FOUND',
                ], 404);
            }

            // Delete the cart item
            $cartItem->delete();

            // After adding the item to the cart, update the count of items in the cart with the active only items
            $cartItems = CartItem::where('user_id', $user->id)->get();

            // Filter out inactive products in the cart
            $cartItemsCount = $cartItems->filter(function ($product) {
                return Product::where('id', $product->product_id)->where('is_active', true)->exists();
            })->count();

            return response()->json([
                'message' => "$product->product_name has been removed from your cart",
                'total_items_in_user_cart' =>  $cartItemsCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while removing the item from the cart',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }
}
