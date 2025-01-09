<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    // Register a new user
    public function register(Request $request): JsonResponse
    {
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
        ]);

        // if the user is created successfully generate a token for the user
        if ($user) {
            $token = $user->createToken($user->name . " Auth-Token")->plainTextToken; // create a token for the user with the user name and Auth-Token

            return response()->json([
                'message' => 'User created successfully',
                'status' => 'success',
                'data' => [
                    'user' => $user,
                    'token' => $token,
                ],
            ], 201);
        } else {
            return response()->json([
                'message' => 'User not created',
                'status' => 'error',
            ], 500);
        }
    }


    // Login a user
    public function login(Request $request): JsonResponse
    {
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
                'status' => 'error',
            ], 401);
        }

        // If the user exists generate a token for the user
        $token = $user->createToken($user->name . " Auth-Token")->plainTextToken; // create a token for the user with the user name and Auth-Token

        return response()->json([
            'message' => 'User logged in successfully',
            'status' => 'success',
            'data' => [
                'user' => $user,
                'token' => $token,
            ],
        ], 200);
    }
}
