<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Store category in database
    public function storeImage(Request $request)
    {
        try {
            // Validate incoming request
            $request->validate([
                'category_name' => 'required|string|max:255',
                'category_slug' => 'required|string|max:255|unique:categories,category_slug',
                'category_description' => 'nullable|string',
                'category_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Optional and 2MB max
            ]);

            // Handle image upload if provided
            $imageName = null;

            // Check if image is provided
            if ($request->hasFile('category_image')) {
                // Generate unique name for the image
                $imageName = time() . '.' . $request->file('category_image')->getClientOriginalExtension();

                // Move image to the public folder
                $request->file('category_image')->move(public_path('category_images'), $imageName);
            }

            // Save category in the database
            Category::create([
                'category_name' => $request->category_name,
                'category_slug' => $request->category_slug,
                'category_description' => $request->category_description,
                'category_image' => $imageName,
            ]);

            // Return JSON response
            return response()->json([
                'message' => "{$request->category_name} category created successfully",
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while uploading the image',
                'errorMessage' => $e->getMessage()
            ], 500);
        }
    }


    // Get all categories
    public function geyAllCategories()
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
