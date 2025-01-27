<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    // Get all products
    public function getAllProducts(): JsonResponse
    {
        try {
            // Get all products
            $products = Product::all();

            // Return the products
            return response()->json($products, 200);
        } catch (\Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], 500);
        }
    }

    // Get a single product by id
    public function getProductById($id): JsonResponse
    {
        try {
            // Get the product
            $product = Product::find($id);

            // Check if the product exists
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            // Return the product
            return response()->json($product, 200);
        } catch (\Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], 500);
        }
    }

    // Create a new product
    public function createProduct(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'product_name' => 'required|string|max:255',
                'product_description' => 'nullable|string',
                'product_price' => 'required|numeric|min:0',
                'product_rating' => 'nullable|integer|min:0|max:5',
                'product_images' => 'nullable|array', // Ensure it's an array
                'product_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // Validate images
                'category_id' => 'required|exists:categories,id',
            ]);

            // Initialize an empty array to store the image paths
            $imageUrls = [];

            // Create the slug from the product name
            $slug = strtolower(str_replace(' ', '-', $validatedData['product_name']));

            // Check if images are provided
            if ($request->hasFile('product_images')) {
                foreach ($request->file('product_images') as $image) {
                    // Generate a unique file name
                    $imageName = uniqid() . '.' . $image->getClientOriginalExtension();

                    // Store the image in storage/app/public/products_images
                    $path = $image->storeAs('products_images', $imageName, 'public');

                    // Push the public URL to the array
                    $imageUrls[] = asset('storage/' . $path);
                }
            }

            // Create the product
            $product = Product::create([
                'product_name' => $validatedData['product_name'],
                'product_description' => $validatedData['product_description'],
                'product_price' => $validatedData['product_price'],
                'product_rating' => $validatedData['product_rating'],
                'slug' => $slug,
                'images' => $imageUrls, // Store as JSON
                // 'is_active' => by default false,
                // 'in_stock' => by default false,
                'category_id' => $validatedData['category_id'],
            ]);


            // Return the product
            return response()->json([
                'message' => "{$product->product_name} created successfully",
                'productData' => $product->load('category') // Eager load the category relationship
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], 500);
        }
    }
}
