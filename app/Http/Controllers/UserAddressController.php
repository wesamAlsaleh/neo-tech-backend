<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;


use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class UserAddressController extends Controller
{
    // Logic to create a new user address
    public function create(Request $request)
    {
        try {
            // validate the request data
            $request->validate([
                'home_number' => 'required|string|max:20|regex:/^[A-Za-z0-9\s\-\.]+$/', // Often called "Building No." in Bahrain
                'street_number' => 'nullable|string|max:10', // Sometimes called "Road No."
                'block_number' => 'required|string|max:10|regex:/^[A-Za-z]?\d+[A-Za-z]?$/', // Called "Block No." (usually 3 digits)
                'city' => 'required|string|max:255|in:Manama,Muharraq,Riffa,Hamad Town,Isa Town,Sitra,Budaiya,Adliya,Amwaj Islands,Arad,Bani Jamra,Barbar,Diyar Al Muharraq,Durrat Al Bahrain,Hidd,Jid Ali,Jidhafs,Juffair,Karrana,Karzakkan,Malikiya,Sanabis,Seef,Saar,Tubli,Zallaq,Bu Quwah,Abu Saiba,Umm Al Hassam,Al Jasra,Nuwaidrat,Samaheej,Busaiteen,Galali,Nabih Saleh,Salmabad,Al Dair,Ghuraifa,Awali,Diraz,Halat Bu Maher,Gudaibiya,Bilad Al Qadeem,Halat Seltah,Khamis,Ma\'ameer,Askar,Hamala,Halat Nuaim,Al Daih,Jaww,Markh,Abu Ithham,Samahij,Karranah,Karzakan,Other', // List of cities in Bahrain
            ]);

            // Get the user who made the request
            $userFromRequest = Auth::user();

            // Get the authenticated user from the database
            $user = User::find($userFromRequest->id);

            // Check if the user exists
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                    'devMessage' => 'USER_NOT_FOUND',
                ], 404);
            }

            // If the user already has an address, update it

            if (UserAddress::where('user_id', $user->id)->exists()) {
                // Get the user's address
                $userAddress = UserAddress::where('user_id', $user->id)->first();

                // Update the user's address
                $userAddress->update([
                    'home_number' => $request->home_number,
                    'street_number' => $request->street_number,
                    'block_number' => $request->block_number,
                    'city' => $request->city,
                ]);

                return response()->json([
                    'message' => "$user->first_name's address updated successfully",
                ], 200);
            }

            // Create a new address for the user
            UserAddress::create([
                'user_id' => $user->id,
                'home_number' => $request->home_number,
                'street_number' => $request->street_number,
                'block_number' => $request->block_number,
                'city' => $request->city,
            ]);

            return response()->json([
                'message' => "$user->first_name's address added successfully",
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
                'message' => 'An error occurred while creating or updating the address',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to update an existing user address
    public function update(Request $request)
    {
        try {
            // validate the request data
            $request->validate([
                'home_number' => 'nullable|string|max:20|regex:/^[A-Za-z0-9\s\-\.]+$/', // Often called "Building No." in Bahrain
                'street_number' => 'nullable|string|max:10', // Sometimes called "Road No."
                'block_number' => 'nullable|string|max:10|regex:/^[A-Za-z]?\d+[A-Za-z]?$/', // Called "Block No." (usually 3 digits)
                'city' => 'nullable|string|max:255|in:Manama,Muharraq,Riffa,Hamad Town,Isa Town,Sitra,Budaiya,Adliya,Amwaj Islands,Arad,Bani Jamra,Barbar,Diyar Al Muharraq,Durrat Al Bahrain,Hidd,Jid Ali,Jidhafs,Juffair,Karrana,Karzakkan,Malikiya,Sanabis,Seef,Saar,Tubli,Zallaq,Bu Quwah,Abu Saiba,Umm Al Hassam,Al Jasra,Nuwaidrat,Samaheej,Busaiteen,Galali,Nabih Saleh,Salmabad,Al Dair,Ghuraifa,Awali,Diraz,Halat Bu Maher,Gudaibiya,Bilad Al Qadeem,Halat Seltah,Khamis,Ma\'ameer,Askar,Hamala,Halat Nuaim,Al Daih,Jaww,Markh,Abu Ithham,Samahij,Karranah,Karzakan,Other', // List of cities in Bahrain
            ]);

            // Get the user who made the request
            $user = Auth::user();

            // Check if the user exists
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                    'devMessage' => 'USER_NOT_FOUND',
                ], 404);
            }

            // User address
            $userAddress = UserAddress::where('user_id', $user->id)->first();

            // Check if the user address exists, if not create a new one
            if (!$userAddress) {
                UserAddress::create([
                    'user_id' => $user->id,
                    'home_number' => $request->home_number,
                    'street_number' => $request->street_number,
                    'block_number' => $request->block_number,
                    'city' => $request->city,
                ]);

                return response()->json([
                    'message' => "$user->first_name's address added successfully",
                ], 200);
            }
            // Update the user's address
            $userAddress->update([
                'home_number' => $request->home_number ?? $userAddress->home_number,
                'street_number' => $request->street_number ?? $userAddress->street_number,
                'block_number' => $request->block_number ?? $userAddress->block_number,
                'city' => $request->city ?? $userAddress->city,
            ]);

            return response()->json([
                'message' => "$user->first_name's address updated successfully",
            ], 200);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User address not found',
                'devMessage' => 'USER_ADDRESS_NOT_FOUND',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the address',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to delete a user address
    public function destroy()
    {
        try {
            // Get the user who made the request
            $user = Auth::user();

            // Check if the user exists
            if (!$user) {
                return response()->json([
                    'message' => 'User not found',
                    'devMessage' => 'USER_NOT_FOUND',
                ], 404);
            }

            // User address
            $userAddress = UserAddress::where('user_id', $user->id)->firstOrFail();

            // Delete the user's address
            $userAddress->delete();

            return response()->json([
                'message' => "$user->first_name's address deleted successfully",
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User address not found',
                'devMessage' => 'USER_ADDRESS_NOT_FOUND',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the address',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }
}
