<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SystemPerformanceLog;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
{

    // Store category in database
    public function createCategory(Request $request)
    {

        try {
            // Validate incoming request
            $request->validate([
                'category_name' => 'required|string|max:255',
                'category_description' => 'nullable|string',
                'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Optional and 2MB max
            ]);

            // Initialize image name
            $imageName = null;

            // Create the slug from the category name
            $slug = strtolower(str_replace(' ', '-', $request->category_name)); // e.g. "My Category" -> "my-category"

            // Check if image is provided
            if ($request->hasFile('category_image')) {
                // Generate a unique file name based on the current timestamp and file extension (e.g. .jpg)
                $imageName = uniqid() . '.' . $request->file('category_image')->getClientOriginalExtension();

                // Store file in the correct folder put(path, file)
                $request->file('category_image')->storeAs('images/categories_images', $imageName, 'public');
            }

            // Save category in the database
            $category = Category::create([
                'category_name' => $request->category_name,
                'category_slug' => $slug,
                'category_description' => $request->category_description,
                'is_active' => false, // Default to false
                'category_image' => $imageName, // Can be null if no image is uploaded
            ]);

            // Add performance log
            SystemPerformanceLog::create([
                'log_type' => 'info',
                'message' => "{$category->category_name} category created successfully",
                'context' => json_encode($category),
                'user_id' => $request->user()->id,
                'status_code' => 201,
            ]);

            // Return JSON response
            return response()->json([
                'message' => "{$category->category_name} category created successfully",
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
                'message' => 'An error occurred while performing the global search.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Get all categories
    public function getAllCategories()
    {
        try {
            // Fetch all categories from the database
            $categories = Category::all();

            // Append the full image URL to each category
            $categories->transform(function ($category) {
                // Check if category has an image
                if ($category->category_image) {
                    // Append the full image URL to the category object
                    $category->category_image_url = asset('storage/images/categories_images/' . $category->category_image); // image URL e.g. http://localhost:8000/storage/images/categories_images/image.jpg
                } else {
                    $category->category_image_url = null; // Optional: Handle categories with no image
                }

                return $category;
            });

            // If no categories are found return a JSON response as empty array
            if ($categories->isEmpty()) {
                return response()->json([
                    'message' => 'No categories found',
                    'categories' => [],
                ], 200);
            }

            // Return the response
            return response()->json([
                'message' => 'Categories fetched successfully',
                'categories' => $categories,
            ], 200);
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json([
                'message' => 'An error occurred while fetching categories',
                'errorMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Get category by id
    public function updateCategoryById(Request $request, string $id)
    {
        try {
            // Validate incoming request
            $validatedData = $request->validate([
                'category_name' => 'nullable|string|max:255',
                'category_description' => 'nullable|string',
                'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Find category by id
            $category = Category::findOrFail($id);

            // Update category name and slug if name is provided
            if (!empty($validatedData['category_name']) && $validatedData['category_name'] !== $category->category_name) {
                $category->category_name = $validatedData['category_name']; // change category name
                $category->category_slug = strtolower(str_replace(' ', '-', $validatedData['category_name'])); // change category slug
            }

            // Handle image upload
            if ($request->hasFile('category_image')) {
                // Delete old image if exists
                if ($category->category_image) {
                    Storage::disk('public')->delete('images/categories_images/' . $category->category_image);
                }

                // Generate and store new image
                $imageName = uniqid() . '.' . $request->file('category_image')->getClientOriginalExtension();

                // Store file in the correct folder storage/app/public/images/categories_images/$imageName
                $request->file('category_image')->storeAs('images/categories_images', $imageName, 'public');

                // Update category image
                $category->category_image = $imageName;
            }

            // Update other fields if provided
            $category->category_description = $validatedData['category_description'] ?? $category->category_description;

            // Save the updated category
            $category->save();

            return response()->json([
                'message' => "{$category->category_name} category updated successfully",
                'category' => $category,
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
                'message' => 'Category not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while performing the global search.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }


    // Delete category by id
    public function deleteCategoryById(string $id)
    {
        try {
            // Check if the ID is numeric otherwise return a JSON response
            if (!is_numeric($id)) {
                return response()->json([
                    'message' => 'Invalid category ID format',
                ], 400);
            }

            // Find category by id
            $category = Category::find($id);

            // Check if category exists
            if (!$category) {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }

            // Delete the category image if it exists
            if ($category->category_image) {
                // Define the file path relative to the storage disk
                $filePath = 'images/categories_images/' . $category->category_image;

                // Check if the file exists in the 'public' disk and delete it
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }
            // Delete category from the database
            $category->delete();

            // Return JSON response
            return response()->json([
                'message' => "{$category->category_name} category deleted successfully"
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the category. Please try again later.',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Toggle category status by id
    public function toggleCategoryStatusById(string $id)
    {
        try {
            // Check if the ID is numeric otherwise return a JSON response
            if (!is_numeric($id)) {
                return response()->json([
                    'message' => 'Invalid category ID format',
                ], 400);
            }

            // Find category by id
            $category = Category::find($id);

            // Check if category exists
            if (!$category) {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }

            // Toggle category status
            $category->is_active = !$category->is_active;
            $category->save();

            // Add performance log
            SystemPerformanceLog::create([
                'log_type' => 'info',
                'message' => "{$category->category_name} category status updated successfully",
                'context' => json_encode($category),
                'user_id' => request()->user()->id,
                'status_code' => 200,
            ]);

            // Return JSON response
            return response()->json([
                'message' => $category->category_name . ' is ' . ($category->is_active ? 'active' : 'inactive'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the category status. Please try again later.',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }
}
