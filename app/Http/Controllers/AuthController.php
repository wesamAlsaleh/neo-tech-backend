<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
                'first_name' => 'required|string|max:255|regex:/^[a-zA-Z]+$/', // only letters and no spaces
                'last_name' => 'required|string|max:255|regex:/^[a-zA-Z]+$/', // only letters and no spaces
                'email' => 'required|email|unique:users|max:255|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', // email format
                'password' => 'required|string|min:8|max:255|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', // password should contain at least one letter and one number
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

            // Get the user's address if it exists
            $userAddress = $user->address()->first();


            return response()->json([
                'message' => 'User retrieved successfully',
                'userData' =>  [
                    'user' => $user,
                    'token' => $token,
                ],
                'userCartItemsCount' => $userCartItemsCount,
                'userWishlistCount' => $userWishlistCount,
                'userAddress' => $userAddress,
            ], 200);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while creating the user, please try again later',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Login a user
    public function login(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'email' => 'required|email|max:255|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', // email format
                'password' => 'required|string|min:8|max:255|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', // password should contain at least one letter and one number
            ]);

            // Check if the user exists in the database
            $user = User::where('email', $request->email)->first();

            // If the user does not exist or the password is incorrect return an error response
            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Email or password is incorrect',
                    'devMessage' => 'INVALID_CREDENTIALS',
                ], 401);
            }

            // If the user exists generate a token for the user
            $token = $user->createToken($user->name . " Auth-Token")->plainTextToken; // create a token for the user with the user name and Auth-Token

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

            // Get the user's address if it exists
            $userAddress = $user->address()->first();


            return response()->json([
                'message' => 'User retrieved successfully',
                'userData' =>  [
                    'user' => $user,
                    'token' => $token,
                ],
                'userCartItemsCount' => $userCartItemsCount,
                'userWishlistCount' => $userWishlistCount,
                'userAddress' => $userAddress,
            ], 200);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Something went wrong while logging in the user, please try again later',
                'devMessage' => $e->getMessage(),
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
                'message' => 'An error occurred while logging out the user',
                'devMessage' => $e->getMessage(),
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

            // Get the user's address if it exists
            $userAddress = $user->address()->first();


            return response()->json([
                'message' => 'User retrieved successfully',
                'userData' => $request->user(),
                'userCartItemsCount' => $userCartItemsCount,
                'userWishlistCount' => $userWishlistCount,
                'userAddress' => $userAddress,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving the user data',
                'devMessage' => $e->getMessage(),
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
                'message' => 'An error occurred while retrieving the user role',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Update the user profile
    public function updateProfile(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'first_name' => 'nullable|string|max:255|regex:/^[a-zA-Z]+$/', // only letters and no spaces
                'last_name' => 'nullable|string|max:255|regex:/^[a-zA-Z]+$/', // only letters and no spaces
                'email' => 'nullable|email|max:255|regex:/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', // email format
                'phone_number' => 'nullable|string|min:8|max:8|regex:/^[0-9]+$/', // 8 digits phone number
            ]);

            // Get the authenticated user
            $user = $request->user();

            // Check if the user is available
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                    'devMessage' => 'USER_NOT_FOUND',
                ], 404);
            }

            // Check if the email already exists in the database
            if ($request->email && User::where('email', $request->email)->where('id', '!=', $user->id)->exists()) {
                return response()->json([
                    'message' => 'Email already exists',
                    'devMessage' => 'EMAIL_ALREADY_EXISTS',
                ], 422);
            }

            // Check if the phone number already exists in the database
            if ($request->phone_number && User::where('phone_number', $request->phone_number)->where('id', '!=', $user->id)->exists()) {
                return response()->json([
                    'message' => 'Phone number already exists',
                    'devMessage' => 'PHONE_NUMBER_ALREADY_EXISTS',
                ], 422);
            }

            // Update the user profile with the provided data or keep the existing data if not provided
            $firstName = $request->first_name ?? $user->first_name;
            $lastName = $request->last_name ?? $user->last_name;
            $email = $request->email ?? $user->email;
            $phoneNumber = $request->phone_number ?? $user->phone_number;

            $user->update([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'email' => $email,
                'phone_number' => $phoneNumber,
            ]);

            return response()->json([
                'message' => "$user->first_name profile updated successfully",
            ], 200);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the user profile',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Change the user password
    public function changePassword(Request $request)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Check if the user is available
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                    'devMessage' => 'USER_NOT_FOUND',
                ], 404);
            }

            // Validate the request
            $request->validate([
                'current_password' => 'required|string|min:8|max:255',
                'new_password' => 'required|string|min:8|max:255|regex:/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$/', // password should contain at least one letter and one number
                'confirm_password' => 'required|string|min:8|max:255', // should be the same as new password
            ]);

            // Check if the current password is correct
            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Your current password is incorrect',
                    'devMessage' => 'INVALID_CURRENT_PASSWORD',
                ], 422);
            }

            // Check if the new password is the same as the confirm password
            if ($request->new_password !== $request->confirm_password) {
                return response()->json([
                    'message' => 'New password and confirm password do not match',
                    'devMessage' => 'PASSWORDS_DO_NOT_MATCH',
                ], 422);
            }

            // Check if the new password is the same as the current password
            if (Hash::check($request->new_password, $user->password)) {
                return response()->json([
                    'message' => 'New password cannot be the same as the current password',
                    'devMessage' => 'NEW_PASSWORD_SAME_AS_CURRENT_PASSWORD',
                ], 422);
            }

            // Update the password in the database
            User::where('id', $user->id)->update([
                'password' => Hash::make($request->new_password),
            ]);

            return response()->json([
                'message' => 'Your password has been changed successfully',
            ], 200);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while changing the user password',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }
}
