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
            //  Get the current page and per page from the url query parameters
            $currentPage = $request->query('page', 1); // Default to page 1 if not provided
            $perPage = $request->query('per_page', 10); // Default to 10 per page if not provided

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
