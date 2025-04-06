<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request): JsonResponse
    {
        // try to create a new user otherwise catch the exception and return an error response with the error message (ex: email already exists)
        try {
            // Validate the request
            $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|email|unique:users|max:255',
                'password' => 'required|string|min:8|max:255',
                'phone_number' => 'required|string|unique:users|min:8|max:8', // 8 digits phone number
            ]);

            // Create the user record in the database and return the user object
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone_number' => $request->phone_number,
                'role' => 'user', // default role is user
            ]);

            // if the user is created successfully generate a token for the user
            if ($user) {
                $token = $user->createToken($user->name . " Auth-Token")->plainTextToken; // create a token for the user with the user name and Auth-Token
            }

            return response()->json([
                'message' => 'User created successfully',
                'userData' => [
                    'user' => $user,
                    'token' => $token,
                ],
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'User not created',
                'errorMessage' => $e->getMessage(),
            ], 500);
        }
    }


    // Login a user
    public function login(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'email' => 'required|email|max:255',
                'password' => 'required|string|min:8|max:255',
            ]);

            // Check if the user exists in the database
            $user = User::where('email', $request->email)->first();

            // If the user does not exist or the password is incorrect return an error response
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Invalid credentials',
                ], 401);
            }

            // If the user exists generate a token for the user
            $token = $user->createToken($user->name . " Auth-Token")->plainTextToken; // create a token for the user with the user name and Auth-Token

            return response()->json([
                'message' => 'User logged in successfully',
                'userData' => [
                    'user' => $user,
                    'token' => $token,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'User not logged in',
                'errorMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logout a user
    public function logout(Request $request): JsonResponse
    {
        try {
            // Revoke the user token
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'User logged out successfully',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'User not logged out',
                'errorMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Get the authenticated user who made the request to the API
    public function user(Request $request): JsonResponse
    {
        try {
            // Get the authenticated user
            $user = $request->user();

            // Check if the user is null
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                    'devMessage' => 'USER_NOT_FOUND',
                ], 404);
            }

            // Eager load products with cartItems and wishlist
            $userCartItems = $user->cartItems()->with('product')->get();
            $userWishlist = $user->wishlist()->with('product')->get();

            // Filter the cart items and wishlist to count only active products
            $userCartItemsCount = $userCartItems->filter(function ($item) {
                return $item->product && $item->product->is_active;
            })->count();

            $userWishlistCount = $userWishlist->filter(function ($item) {
                return $item->product && $item->product->is_active;
            })->count();

            return response()->json([
                'message' => 'User retrieved successfully',
                'userData' => $request->user(),
                'userCartItemsCount' => $userCartItemsCount,
                'userWishlistCount' => $userWishlistCount,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'User not retrieved',
                'errorMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // get the user role who made the request to the API
    public function userRole(Request $request): JsonResponse
    {
        try {
            return response()->json([
                'message' => 'User role retrieved successfully',
                'userRole' => $request->user()->role,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'User role not retrieved',
                'errorMessage' => $e->getMessage(),
            ], 500);
        }
    }
}
