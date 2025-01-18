<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Store category in database
    public function createCategory(Request $request)
    {
        try {
            // Validate incoming request
            $request->validate([
                'category_name' => 'required|string|max:255',
                'category_slug' => 'required|string|max:255|unique:categories,category_slug',
                'category_description' => 'nullable|string',
                'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Optional and 2MB max
            ]);

            // Initialize image name
            $imageName = null;

            // Check if image is provided
            if ($request->file('category_image')->isValid()) {
                // Generate a unique file name based on the current timestamp and file extension (e.g. .jpg)
                $imageName = uniqid() . '.' . $request->file('category_image')->getClientOriginalExtension();

                // Move image to storage (recommended over public_path for security)
                $request->file('category_image')->storeAs('public/categories_images', $imageName);
            }

            // Save category in the database
            $category = Category::create([
                'category_name' => $request->category_name,
                'category_slug' => $request->category_slug,
                'category_description' => $request->category_description,
                'category_image' => $imageName, // Can be null if no image is uploaded
            ]);

            // Return JSON response
            return response()->json([
                'message' => "{$category->category_name} category created successfully",
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
            return response()->json([
                'categories' => Category::all()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching categories',
                'errorMessage' => $e->getMessage()
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

    // TODO: Update category by id
    // TODO: Delete category by id
    // TODO: Search category by name
    // TODO: Search category by slug
}
