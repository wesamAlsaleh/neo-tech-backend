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
    // Get all shop features
    public function index()
    {
        try {
            $ShopFeatures = ShopFeature::all();

            // If there are no shop features, return an empty array
            if ($ShopFeatures->isEmpty()) {
                return response()->json([
                    'message' => 'No shop features found',
                    'features' => [],
                ]);
            }

            return response()->json([
                'message' => 'Get all shop features',
                'features' => $ShopFeatures->toArray(),
            ]);
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
            $url = asset(str_replace('public/', 'storage/', $path));


            // Add the ShopFeature to the database
            $ShopFeature = ShopFeature::create([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
                'color' => $validatedData['color'],
                'icon' => $url,
            ]);

            return response()->json([
                'message' => 'Shop feature added successfully',
                'feature' => $ShopFeature->toArray(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
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
                $shopFeature->icon = asset('storage/' . $path);
            }

            // Update other fields if provided, or keep old values
            $shopFeature->name = $validatedData['name'] ?? $shopFeature->name;
            $shopFeature->description = $validatedData['description'] ?? $shopFeature->description;
            $shopFeature->color = $validatedData['color'] ?? $shopFeature->color;

            // Save changes
            $shopFeature->save();

            return response()->json([
                'message' => 'Shop feature updated successfully',
                'feature' => $shopFeature,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
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
}
