<?php

namespace App\Http\Controllers;

use App\Models\Image;
use Illuminate\Support\Facades\Auth;

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\File\Exception\FileException;



class ImageController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            // Get all the images from the database
            $images = Image::all();

            // Return the images in JSON format
            return response()->json([
                'message' => 'Images fetched successfully',
                'images' => $images
            ]);
        } catch (\Exception $e) {
            Log::error('Unexpected Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred, please try again.',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'name' => 'required|string|unique:images,name',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'visibility' => 'required|string', // enum: ['public', 'members']
            ]);

            // If the image is empty, return an error
            if (!$request->hasFile('image')) {
                return response()->json([
                    'message' => 'Image is required',
                    'devMessage' => 'IMAGE_REQUIRED'
                ], 422);
            }

            // Generate the image name by concatenating the name and the extension of the image, e.g., "image_ex.jpg"
            $imageName = str_replace(' ', '_', $validatedData['name']) . '.' . $request->image->extension();

            // Store the Slider image in the "images/slider_images" directory
            $path = $request->image->storeAs("images/slider_images", $imageName, 'public');

            // Generate the public URL (accessible via the browser) of the image
            $url = url('storage/' . $path);

            // Create a new image record in the database
            Image::create([
                'name' => $validatedData['name'],
                'url' => $url,
                'visibility' => $validatedData['visibility']
            ]);

            return response()->json([
                'message' => "$validatedData[name] is uploaded successfully",
            ], 201);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Image update failed, please try again.',
                'devMessage' => 'IMAGE_UPDATE_ERROR'
            ], 500);
        } catch (FileException $e) {
            return response()->json([
                'message' => 'File upload failed, please try again.',
                'devMessage' => 'FILE_UPLOAD_ERROR'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred, please try again.',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'name' => 'string|unique:images,name,' . $id,
                'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            // Find the image record in the database
            $image = Image::findOrFail($id);

            // Get the old name of the image
            $oldName = $image->name;

            // Get the old image path
            $imagePath = str_replace('/storage/', '', parse_url($image->url, PHP_URL_PATH));

            // Delete the old image file from the public disk using the path
            Storage::disk('public')->delete($imagePath);

            // Generate the new image name by concatenating the name and the extension of the image, e.g., "new_image_ex.jpg"
            $imageName = str_replace(' ', '_', $validatedData['name']) . '.' . $request->image->extension();

            // Store the Slider image in the "images/slider_images" directory
            $path = $request->image->storeAs("images/slider_images", $imageName, 'public');

            // Generate the public URL (accessible via the browser) of the image
            $url = url('storage/' . $path);

            // Update the image record in the database
            $image->update([
                'name' => $validatedData['name'],
                'url' => $url,
            ]);

            return response()->json([
                'message' => "$oldName is updated successfully to $validatedData[name]",
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation error, please check your input',
                'devMessage' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Image not found in the database',
                'devMessage' => 'IMAGE_NOT_FOUND'
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Image update failed, please try again.',
                'devMessage' => 'IMAGE_UPDATE_ERROR'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred, please try again.',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // Find the image record in the database
            $image = Image::findOrFail($id);

            // Get the old image path
            $imagePath = str_replace('/storage/', '', parse_url($image->url, PHP_URL_PATH));

            // If there is no image after deleting the image, return an error
            if (Image::count() == 1) {
                return response()->json([
                    'message' => 'At least one image must be available',
                    'devMessage' => 'LAST_IMAGE_ERROR'
                ], 422);
            }


            // Delete the old image file from the public disk using the path
            Storage::disk('public')->delete($imagePath);

            return response()->json([
                'message' => "$image->name is deleted successfully",
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Image not found in the database',
                'devMessage' => 'IMAGE_NOT_FOUND'
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Image deletion failed, please try again.',
                'devMessage' => 'IMAGE_DELETION_ERROR'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred, please try again.',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the active status of the image.
     */
    public function toggleImageActivity(string $id)
    {
        try {
            // Find the image record in the database
            $image = Image::findOrFail($id);

            // If the image is not active and the total active images are 10 or more, return an error that only 10 images can be active at a time
            if (!$image->is_active && Image::where('is_active', true)->count() >= 10) {
                return response()->json([
                    'message' => 'Only 10 images can be active at a time',
                    'devMessage' => 'MAX_ACTIVE_IMAGES'
                ], 422);
            }

            // If the image is the only active image, return an error that at least one image must be active
            if ($image->is_active && Image::where('is_active', true)->count() == 1) {
                return response()->json([
                    'message' => 'At least one image must be active',
                    'devMessage' => 'LAST_ACTIVE_IMAGE_ERROR'
                ], 422);
            }

            // Toggle the active status of the image
            $image->update([
                'is_active' => !$image->is_active
            ]);

            return response()->json([
                'message' => "$image->name is now " . ($image->is_active ? 'active' : 'inactive'),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Image not found in the database',
                'devMessage' => 'IMAGE_NOT_FOUND'
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Image active status toggle failed, please try again.',
                'devMessage' => 'IMAGE_ACTIVE_STATUS_TOGGLE_ERROR'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred, please try again.',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle the visibility of the image.
     */
    public function toggleImageVisibility(string $id)
    {
        try {
            // Find the image record in the database
            $image = Image::findOrFail($id);

            // If the image is the only public image, return an error that at least one image must be public
            if ($image->visibility === 'public' && Image::where('visibility', 'public')->count() == 1) {
                return response()->json([
                    'message' => 'At least one image must be public',
                    'devMessage' => 'LAST_PUBLIC_IMAGE_ERROR'
                ], 422);
            }

            // Toggle the visibility of the image
            $image->update([
                'visibility' => $image->visibility === 'public' ? 'members' : 'public'
            ]);

            return response()->json([
                'message' => "$image->name now is for $image->visibility",
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Image not found in the database',
                'devMessage' => 'IMAGE_NOT_FOUND'
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Image visibility toggle failed, please try again.',
                'devMessage' => 'IMAGE_VISIBILITY_TOGGLE_ERROR'
            ], 500);
        } catch (\Exception $e) {
            Log::error('Unexpected Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred, please try again.',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the resource for the slider.
     */
    public function display(Request $request)
    {
        try {
            // Get the token from the request if it exists
            $token = $request->bearerToken();

            // Check if the user is authenticated by checking the token if it exists
            if ($token) {
                // Get all the active images from the database (should be 10 or less)
                $allImages = Image::where('is_active', true)->get();

                // The user is logged in and authenticated
                return response()->json([
                    'message' => 'Images fetched successfully',
                    'visibility' => 'limited to authenticated user',
                    'images' => $allImages->values()  // Return all the images whether they are public or members-only (values() to re-index the collection to avoid numeric keys)
                ]);
            } else {
                // The user is not logged in
                // Filter the images to return only the public images
                $publicImages = Image::where('is_active', true)
                    ->where('visibility', 'public')
                    ->get();

                return response()->json([
                    'message' => 'Images fetched successfully',
                    'visibility' => 'all users',
                    'images' => $publicImages->values()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Unexpected Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred, please try again.',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }
}
