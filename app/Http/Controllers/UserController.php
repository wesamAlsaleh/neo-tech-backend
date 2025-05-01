<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    // Logic to get all users with pagination
    public function index(Request $request)
    {
        try {
            //  Get the current page and per page from the url query parameters
            $currentPage = $request->query('page', 1); // Default to page 1 if not provided
            $perPage = $request->query('per_page', 10); // Default to 10 per page if not provided

            // Fetch users from the database with pagination
            $users = User::OrderBy('created_at', 'desc')
                ->paginate(
                    $perPage, // Default to 10 per page if not provided
                    ['*'], // Get all columns
                    'users_index', // Custom pagination page name
                    $currentPage // Default to page 1 if not provided
                );

            return response()->json([
                'message' => 'Users retrieved successfully',
                'users' => $users,
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
                'message' => 'An error occurred while performing the global search.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to get a single user by ID
    public function show($id)
    {
        try {
            // Fetch user from the database by ID
            $user = User::findOrFail($id);

            // Bring the user orders
            $user->load('orders');

            return response()->json([
                'message' => 'User retrieved successfully',
                'user' => $user,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found',
                'devMessage' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving the user.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // TODO: Logic to update a user by ID
    public function update(Request $request, $id)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255|unique:users,email' . $id,
                'password' => 'nullable|string|min:8|confirmed',
                'phone_number' => 'nullable|string|max:255',
            ]);

            // Fetch user from the database by ID
            $user = User::findOrFail($id);

            // Update user with validated data
            $user->update($validatedData);

            return response()->json([
                'message' => 'User updated successfully',
                'user' => $user,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found',
                'devMessage' => $e->getMessage(),
            ], 404);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the user.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }
}
