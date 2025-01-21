<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CategoryController extends Controller
{
    // TODO: Remove Error Messages from the response due to security reasons in production

    // Store category in database
    public function createCategory(Request $request)
    {
        // Validate incoming request
        $request->validate([
            'category_name' => 'required|string|max:255',
            'category_description' => 'nullable|string',
            'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Optional and 2MB max
        ]);

        try {

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

            // Return JSON response
            return response()->json([
                'message' => "{$category->category_name} category created successfully",
                // 'request' => $request->all(),
                // 'category' => $category
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the category. Please try again later.',
                'errorMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Get all categories
    public function getAllCategories()
    {
        try {
            // TODO: Add Pagination
            // $categories = Category::paginate(10); // 10 categories per page

            // Fetch all categories from the database
            $categories = Category::all();

            // If categories are empty, return a JSON response
            if ($categories->isEmpty()) {
                return response()->json([
                    'message' => 'No categories found',
                    'categories' => [],
                ], 404);
            }

            // Append the full image URL to each category
            $categories->transform(function ($category) {
                // Check if category has an image
                if ($category->category_image) {
                    // Append the full image URL to the category object
                    $category->category_image_url = asset('storage/images/categories_images/' . $category->category_image); // image URL e.g. http://localhost:8000/storage/images/categories_images/image.jpg
                } else {
                    $category->category_image_url = null; // Optional: Handle categories with no image

                    // TODO: Add placeholder image URL if no image is available
                    // $category->category_image_url = $category->category_image
                    //     ? asset('storage/images/categories_images/' . $category->category_image)
                    //     : asset('images/default-category-placeholder.jpg');
                }

                return $category;
            });

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
    public function getCategoryById(string $id)
    {
        // Check if the ID is numeric otherwise return a JSON response
        if (!is_numeric($id)) {
            return response()->json([
                'message' => 'Invalid category ID format',
            ], 400);
        }

        try {
            // Find category by id
            $category = Category::find($id);

            // Check if category exists and return JSON response
            if ($category) {
                // Get full image URL if an image exists
                $imageUrl = $category->category_image
                    ? asset('storage/images/categories_images/' . $category->category_image)
                    : null;

                // TODO: Add placeholder image URL if no image is available
                // $imageUrl = $category->category_image
                //     ? asset('storage/images/categories_images/' . $category->category_image)
                //     : asset('images/default-category-placeholder.jpg');

                return response()->json([
                    'category' => [
                        'id' => $category->id,
                        'category_name' => $category->category_name,
                        'category_slug' => $category->category_slug,
                        'category_description' => $category->category_description,
                        'is_active' => $category->is_active,
                        'category_image_url' => $imageUrl,
                    ],
                ], 200);
            } else {
                // Return JSON response if category is not found
                return response()->json([
                    'message' => 'Category not found',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching category',
                'errorMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Update category by id
    public function updateCategoryById(Request $request, string $id)
    {
        // Check if the ID is numeric otherwise return a JSON response
        if (!is_numeric($id)) {
            return response()->json([
                'message' => 'Invalid category ID format',
            ], 400);
        }

        // Validate incoming request
        $validatedData = $request->validate([
            'category_name' => 'nullable|string|max:255',
            'category_description' => 'nullable|string',
            'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Optional and 2MB max
        ]);

        DB::beginTransaction(); // Start a database transaction to ensure data integrity

        try {
            // Find category by id
            $category = Category::find($id);

            // Check if category exists
            if (!$category) {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }

            // Initialize old image name
            $imageName = $category->category_image;


            // Update category name and slug if name is provided if(the category name is not empty and different from the current name)
            if (!empty($validatedData['category_name']) && $validatedData['category_name'] !== $category->category_name) {
                // Update the category name
                $category->category_name = $validatedData['category_name'];

                // Update the category slug
                $category->category_slug = strtolower(str_replace(' ', '-', $validatedData['category_name']));
            }


            // Check if a new image is uploaded
            if ($request->hasFile('category_image')) {
                // Delete the old image if it exists if to replace it with the requested one (there is an old image and the file exists in the storage disk)
                if ($category->category_image && Storage::disk('public')->exists('images/categories_images/' . $category->category_image)) {
                    // Delete the old image
                    Storage::disk('public')->delete('images/categories_images/' . $category->category_image);
                }

                // Generate a new image name and store the file
                $imageName = uniqid() . '.' . $request->file('category_image')->getClientOriginalExtension();

                // Store the new image in the correct folder storage/app/public/images/categories_images
                $request->file('category_image')->storeAs('images/categories_images', $imageName, 'public');
            }

            // Update other fields if provided
            $category->category_description = $validatedData['category_description'] ?? $category->category_description;
            $category->category_image = $imageName;

            // Save the updated category
            $category->save();

            // Return JSON response
            return response()->json([
                'message' => "{$category->category_name} category updated successfully",
                // 'category' => $category,
            ], 200);

            DB::commit(); // Commit the transaction if everything is successful and no exceptions are thrown
        } catch (\Exception $e) {
            DB::rollBack(); // Rollback the transaction if an exception occurs
            return response()->json([
                'message' => 'An error occurred while updating the category. Please try again later.',
                'errorMessage' => $e->getMessage(),
                'request' => $request->all()
            ], 500);
        }
    }


    // Delete category by id
    public function deleteCategoryById(string $id)
    {
        // Check if the ID is numeric otherwise return a JSON response
        if (!is_numeric($id)) {
            return response()->json([
                'message' => 'Invalid category ID format',
            ], 400);
        }

        try {
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
                'errorMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Toggle category status by id
    public function toggleCategoryStatusById(string $id)
    {
        // Check if the ID is numeric otherwise return a JSON response
        if (!is_numeric($id)) {
            return response()->json([
                'message' => 'Invalid category ID format',
            ], 400);
        }

        try {
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

            // Return JSON response
            return response()->json([
                'message' => $category->category_name . ' is ' . ($category->is_active ? 'active' : 'inactive'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the category status. Please try again later.',
                'errorMessage' => $e->getMessage()
            ], 500);
        }
    }
}
