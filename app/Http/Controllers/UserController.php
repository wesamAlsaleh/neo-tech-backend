<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    // Logic to get all users with pagination
    public function index(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'perPage' => 'nullable|integer|min:1|max:50', // Number of products per page
                'page' => 'nullable|integer|min:1', // Number of the current page
            ]);

            // Get the current page and items per page from the request
            $currentPage = $request->input('page') ?? 1; // Default to page 1 if not provided
            $perPage = $request->input('perPage') ?? 10; // Default to 10 items per page if not provided

            // Fetch users from the database with pagination
            $users = User::paginate(
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
}
