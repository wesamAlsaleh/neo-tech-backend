<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

            // Bring the user addresses
            $user->load('address');

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

    //  Logic to update a user by ID
    public function update(Request $request, $id)
    {
        try {
            // Validate the request data
            $validatedData = $request->validate([
                'first_name' => 'nullable|string|max:255',
                'last_name' => 'nullable|string|max:255',
                'email' => 'nullable|email|max:255',
                'password' => 'nullable|string|min:8',
                'phone_number' => 'nullable|max:255|min:8',
                'role' => 'nullable|string|in:admin,user',
                'home_number' => 'nullable|string|max:10',
                'street_number' => 'nullable|string|max:10',
                'block_number' => 'nullable|string|max:10',
                'city' => 'nullable|string|max:255',
            ]);

            // Fetch user from the database by ID
            $user = User::with(['address'])->findOrFail($id);

            // Prepare update data
            $personalData = [];
            $addressData = [];

            // Check and prepare personal data
            if (isset($validatedData['first_name'])) $personalData['first_name'] = $validatedData['first_name'];
            if (isset($validatedData['last_name'])) $personalData['last_name'] = $validatedData['last_name'];
            if (isset($validatedData['email'])) {
                // Check if the email is already taken by another user
                $existingUser = User::where('email', $validatedData['email'])
                    ->where('id', '!=', $user->id) // if the user who has the email is not the same as the one being updated send error
                    ->first();

                if ($existingUser) {
                    return response()->json([
                        'message' => 'Email already taken by another user',
                        'devMessage' => 'EMAIL_TAKEN',
                    ], 422);
                }

                // If the email is not taken, update it
                $personalData['email'] = $validatedData['email'];
            }
            if (isset($validatedData['phone_number'])) {
                // Check if the phone number is already taken by another user
                $existingUser = User::where('phone_number', $validatedData['phone_number'])
                    ->where('id', '!=', $user->id) // if the user who has the phone number is not the same as the one being updated send error
                    ->first();

                if ($existingUser) {
                    return response()->json([
                        'message' => 'Phone number already taken by another user',
                        'devMessage' => 'PHONE_NUMBER_TAKEN',
                    ], 422);
                }

                // If the phone number is not taken, update it
                $personalData['phone_number'] = $validatedData['phone_number'];
            }
            if (isset($validatedData['role'])) $personalData['role'] = $validatedData['role'];
            if (isset($validatedData['password'])) {
                $personalData['password'] = Hash::make($validatedData['password']);
            }

            // Check and prepare address data
            if (isset($validatedData['home_number'])) $addressData['home_number'] = $validatedData['home_number'];
            if (isset($validatedData['street_number'])) $addressData['street_number'] = $validatedData['street_number'];
            if (isset($validatedData['block_number'])) $addressData['block_number'] = $validatedData['block_number'];
            if (isset($validatedData['city'])) $addressData['city'] = $validatedData['city'];

            // Update user data if there are changes
            if (!empty($personalData)) {
                $user->update($personalData);
            }

            if (!empty($addressData)) {
                // If the user has an address, update it; otherwise, create a new one
                if ($user->address) {
                    $user->address()->update($addressData);
                } else {
                    $user->address()->create($addressData);
                }
            }

            // Reload the user with relationships (to get the updated data)
            $user->load('address');

            return response()->json([
                'message' => "{$user->first_name} {$user->last_name} profile updated successfully",
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
