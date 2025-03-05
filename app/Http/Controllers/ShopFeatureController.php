<?php

namespace App\Http\Controllers;

use App\Models\ShopFeature;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

// Error handling imports
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class ShopFeatureController extends Controller
{
    // Get all shop features (admin-side)
    public function index(Request $request)
    {
        try {
            // validate the incoming request to get the page number
            $request->validate([
                'page' => 'integer|min:1', // Ensure the page number is an integer and greater than 0
            ]);

            // Get the page number from the request, or default to 1 if not provided
            $request->query('page') ?? 1; // e.g. /api/shop-features?page=1

            // Get all shop features from the database with pagination
            $ShopFeatures = ShopFeature::paginate(5);

            // If there are no shop features, return an empty array
            if ($ShopFeatures->isEmpty()) {
                return response()->json([
                    'message' => 'No shop features found',
                    'features' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'total_pages' => 1,
                    ],
                ], 200);
            }

            return response()->json([
                'message' => 'Get all shop features',
                'features' => $ShopFeatures->items(),  // The current page items
                'pagination' => [
                    'current_page' => $ShopFeatures->currentPage(),
                    'total_pages' => $ShopFeatures->lastPage(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get all shop features',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Add a shop feature
    public function store(Request $request)
    {
        try {
            // Validate the incoming request
            $validatedData = $request->validate([
                'name' => 'required|string|unique:shop_features,name',
                'description' => 'required|string',
                'color' => 'required|string',
                'icon' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:1024', // Max 1MB file size
            ]);

            // Generate a slug for consistency
            $slug = Str::slug($validatedData['name']);
            $iconName = $slug . '.' . $request->icon->extension(); // e.g. "shop_feature_name.jpg"

            // Store the ShopFeature icon in the "images/shop_features_icons" directory
            $path = $request->icon->storeAs("images/shop_features_icons", $iconName, 'public');

            // Generate the public URL (accessible via the browser)
            // $url = asset(str_replace('public/', 'storage/', $path));
            $url = $url = url('storage/' . $path);

            // Add the ShopFeature to the database
            $ShopFeature = ShopFeature::create([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
                'color' => $validatedData['color'],
                'icon' => $url,
            ]);

            return response()->json([
                'message' => "$ShopFeature->name added successfully",
                'feature' => $ShopFeature->toArray(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => explode(':', $e->getMessage())[1], // Get the error message without "The name field is required."
                'devMessage' => $e->errors(),
            ], 422);
        } catch (FileException $e) {
            Log::error('File Upload Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'File upload failed, please try again.',
                'devMessage' => 'FILE_UPLOAD_ERROR'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred. Please contact support.',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Edit a shop feature
    public function update(Request $request, String $id)
    {
        try {
            // Validate request
            $validatedData = $request->validate([
                'name' => "nullable|string|unique:shop_features,name,{$id}", // Ignore current ID, if the name is not changed it will not be unique
                'description' => 'nullable|string',
                'color' => 'nullable|string',
                'icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:1024', // Max 1MB
            ]);

            // Find the ShopFeature by ID
            $shopFeature = ShopFeature::find($id);

            // If the ShopFeature does not exist, return an error
            if (!$shopFeature) {
                return response()->json([
                    'message' => 'Shop feature not found',
                    'devMessage' => 'SHOP_FEATURE_NOT_FOUND'
                ], 404);
            }

            // Handle icon update (if a new file is uploaded)
            if ($request->hasFile('icon')) {
                // Delete old icon (if exists)
                if ($shopFeature->icon) {
                    // Get the old icon path
                    $oldIconPath = str_replace('/storage/', '', parse_url($shopFeature->icon, PHP_URL_PATH));

                    // Delete the old icon
                    Storage::disk('public')->delete($oldIconPath);
                }

                // Generate new file name (fallback if name is missing)
                $slug = isset($validatedData['name']) ? Str::slug($validatedData['name']) : 'shop_feature_' . time(); // e.g. "shop_feature_EnteredName" or "shop_feature_1631234567"

                // Generate the new icon name e.g. "shop_feature_name.jpg"
                $iconName = $slug . '.' . $request->icon->extension();

                // Store the icon and get the path
                $path = $request->icon->storeAs("images/shop_features_icons", $iconName, 'public');

                // Update icon URL in the database
                $shopFeature->icon = url('storage/' . $path);
            }

            // Update other fields if provided, or keep old values
            $shopFeature->name = $validatedData['name'] ?? $shopFeature->name;
            $shopFeature->description = $validatedData['description'] ?? $shopFeature->description;
            $shopFeature->color = $validatedData['color'] ?? $shopFeature->color;

            // Save changes
            $shopFeature->save();

            return response()->json([
                'message' => "$shopFeature->name updated successfully",
                'feature' => $shopFeature,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => explode(':', $e->getMessage())[1], // Get the error message without "The name field is required."
                'devMessage' => $e->errors(),
            ], 422);
        } catch (FileException $e) {
            Log::error('File Upload Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'File upload failed, please try again.',
                'devMessage' => 'FILE_UPLOAD_ERROR'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred. Please contact support.',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a shop feature with hard delete
    public function destroy(String $id)
    {
        try {
            // Find the ShopFeature by ID
            $shopFeature = ShopFeature::find($id);

            // If the ShopFeature does not exist, return an error
            if (!$shopFeature) {
                return response()->json([
                    'message' => 'Shop feature not found',
                    'devMessage' => 'SHOP_FEATURE_NOT_FOUND'
                ], 404);
            }

            // Delete the icon (if exists)
            if ($shopFeature->icon) {
                // Get the icon path
                $iconPath = str_replace('/storage/', '', parse_url($shopFeature->icon, PHP_URL_PATH));

                // Delete the icon
                Storage::disk('public')->delete($iconPath);
            }

            // Delete the ShopFeature
            $shopFeature->delete();

            return response()->json([
                'message' => "$shopFeature->name deleted successfully",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete shop feature',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Toggle the status of a shop feature
    public function toggleFeatureStatusById(String $id)
    {
        try {
            // Find the ShopFeature by ID
            $shopFeature = ShopFeature::find($id);

            // If the ShopFeature does not exist, return an error
            if (!$shopFeature) {
                return response()->json([
                    'message' => 'Shop feature not found',
                    'devMessage' => 'SHOP_FEATURE_NOT_FOUND'
                ], 404);
            }

            // Get the current count of active features to prevent activating more than 3 features
            $activeCount = ShopFeature::where('is_active', true)->count(); // 1 is true, 0 is false

            // If trying to activate a feature and there are already 3 active features
            if ($shopFeature->is_active == false && $activeCount >= 3) {
                return response()->json([
                    'message' => 'Cannot activate more than 3 features. Please deactivate one first.',
                    'devMessage' => 'MAX_ACTIVE_FEATURES_REACHED'
                ], 400);
            }

            // Toggle the status
            $shopFeature->is_active = !$shopFeature->is_active;

            // Save changes
            $shopFeature->save();

            return response()->json([
                'message' => "$shopFeature->name is now " . ($shopFeature->is_active ? 'active' : 'not active'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to toggle shop feature status',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Get active shop features (only 3 can be active at a time for the client-side)
    public function getActiveFeatures()
    {
        try {
            // Get all active shop features from the database (should be <= 3)
            $activeFeatures = ShopFeature::where('is_active', true)->get();

            // If there are no active shop features, return an empty array
            if ($activeFeatures->isEmpty()) {
                return response()->json([
                    'message' => 'No active shop features found',
                    'features' => [],
                ], 404);
            }

            return response()->json([
                'message' => 'Active shop features found',
                'features' => $activeFeatures->toArray(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get active shop features',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }
}
