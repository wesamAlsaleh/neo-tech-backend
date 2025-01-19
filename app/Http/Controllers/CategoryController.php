<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

                // Store the image in the storage/app/private/images/categories_images directory
                $request->file('category_image')->storeAs('images/categories_images', $imageName);
            }

            // Save category in the database
            $category = Category::create([
                'category_name' => $request->category_name,
                'category_slug' => $slug,
                'category_description' => $request->category_description,
                'category_image' => $imageName, // Can be null if no image is uploaded
            ]);

            // Return JSON response
            return response()->json([
                'message' => "{$category->category_name} category created successfully",
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
            // Fetch all categories from the database
            $categories = Category::all();

            // If categories are empty, return a JSON response
            if ($categories->isEmpty()) {
                return response()->json([
                    'message' => 'Categories fetched successfully',
                    'categories' => 'No categories found',
                ], 404);
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
    public function getCategoryById($id)
    {
        try {
            // Find category by id
            $category = Category::find($id);

            // Check if category exists and return JSON response
            if ($category) {
                return response()->json([
                    'category' => $category
                ], 200);
            } else {
                // Return JSON response if category is not found
                return response()->json(
                    [
                        'message' => 'Category not found'
                    ],
                    404
                );
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
        try {
            // Validate incoming request
            $request->validate([
                'category_name' => 'nullable|string|max:255',
                'category_description' => 'nullable|string',
                'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Optional and 2MB max
            ]);


            // Find category by id
            $category = Category::find($id);

            // Initialize old image name and category name and slug from the database
            $imageName = $category->category_image;
            $categoryName = $category->category_name;
            $categorySlugName = $category->category_slug;


            // Check if category exists
            if (!$category) {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }



            // If the category name has been updated, generate a new name and slug
            if ($request->category_name !== $categoryName) {
                $categoryName = $request->category_name; // Update the name
                $categorySlugName = strtolower(str_replace(' ', '-', $request->category_name)); // Generate new slug based on the new name
            }


            // Check if image is provided
            if ($request->hasFile('category_image')) {
                // Delete the old image if it exists
                if ($category->category_image && Storage::exists('images/categories_images/' . $category->category_image)) {
                    Storage::delete('images/categories_images/' . $category->category_image);
                }

                // Generate a new image name
                $imageName = uniqid() . '.' . $request->file('category_image')->getClientOriginalExtension();

                // Store the new image in the images/categories_images directory
                $request->file('category_image')->storeAs('images/categories_images', $imageName);
            }

            // Update category in the database with validated fields
            $category->update([
                'category_name' => $categoryName,
                'category_slug' => $categorySlugName,
                'category_description' => $request->category_description ?? $category->category_description,
                'category_image' => $imageName, // Update image name if new image is uploaded
            ]);

            // Return JSON response
            return response()->json([
                // 'message' => "{$category->category_name} category updated successfully",
                'category' => $category,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the category. Please try again later.',
                'errorMessage' => $e->getMessage(),
                'request' => $request->all()
            ], 500);
        }
    }


    // Delete category by id
    public function deleteCategoryById($id)
    {
        try {
            // Find category by id
            $category = Category::find($id);

            // Check if category exists
            if (!$category) {
                return response()->json([
                    'message' => 'Category not found'
                ], 404);
            }

            // Delete category from the database
            $category->delete();

            // Delete the category image if it exists
            if ($category->category_image) {
                // Check if the image exists in the directory before attempting to delete it
                if (Storage::exists('images/categories_images/' . $category->category_image)) {
                    // Delete the image from the storage
                    Storage::delete('images/categories_images/' . $category->category_image);
                }
            }

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

    // TODO: Search category by name
    // public function searchCategoryByName($name)
    // {
    //     try {
    //         // Search for categories by name
    //         $categories = Category::where('category_name', 'LIKE', '%' . $name . '%')->get();

    //         // Check if any categories are found
    //         if ($categories->isEmpty()) {
    //             return response()->json([
    //                 'message' => 'No categories found with the given name'
    //             ], 404);
    //         }

    //         // Return JSON response with the found categories
    //         return response()->json([
    //             'message' => 'Categories found successfully',
    //             'categories' => $categories
    //         ], 200);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'An error occurred while searching for categories',
    //             'errorMessage' => $e->getMessage()
    //         ], 500);
    //     }
    // }

    // TODO: Search category by slug
}
