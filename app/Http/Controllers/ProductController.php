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

            // Initialize an empty array to store the image paths as URLs (public)
            $imageUrls = [];

            // Create the slug from the product name
            $slug = strtolower(str_replace(' ', '-', $validatedData['product_name']));

            // Check if images are provided
            if ($request->hasFile('product_images')) {
                foreach ($request->file('product_images') as $image) {
                    // Generate a unique file name
                    $imageName = uniqid() . '.' . $image->getClientOriginalExtension();

                    // Store the image in storage/app/public/images/product_images
                    $path = $image->storeAs('images/products_images', $imageName, 'public');

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
                'category_id' => $validatedData['category_id'],
                // 'is_active' => by default false,
                // 'in_stock' => by default false,
            ]);


            // Return the product
            return response()->json([
                'message' => "{$product->product_name} created successfully",
                'productData' => $product->load('category')
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], 500);
        }
    }

    // Update a product by id
    public function updateProductById(Request $request, $id): JsonResponse
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'product_name' => 'nullable|string|max:255',
                'product_description' => 'nullable|string',
                'product_price' => 'nullable|numeric|min:0',
                'product_rating' => 'nullable|integer|min:0|max:5',
                'product_images' => 'nullable|array', // Ensure it's an array
                'product_images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048', // Validate images
                'category_id' => 'required|exists:categories,id',
            ]);

            // Find the product
            $product = Product::findOrFail($id);

            // Check if the product exists
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            // Initialize an empty array to store the new image URLs
            $imageUrls = $product->images ?? []; // Keep existing images if not updated

            // If new images are uploaded, replace the old ones
            if ($request->hasFile('product_images')) {
                // Delete old images from storage (optional)
                foreach ($product->images as $oldImage) {
                    // Get the path of the image
                    $oldImagePath = str_replace(asset('storage/'), '', $oldImage);

                    // Delete the image from storage
                    Storage::disk('public')->delete($oldImagePath);
                }

                $imageUrls = []; // Reset images

                // Store the new images
                foreach ($request->file('product_images') as $image) {
                    // Generate a unique file name for the image e.g. 612f7b7b618f4.jpg
                    $imageName = uniqid() . '.' . $image->getClientOriginalExtension();

                    // Store the image in storage/app/public/products_images
                    $path = $image->storeAs('products_images', $imageName, 'public');

                    // Push the public URL to the array
                    $imageUrls[] = asset('storage/' . $path);
                }
            }

            // Update the product details
            $product->update([
                'product_name' => $validatedData['product_name'],
                'product_description' => $validatedData['product_description'],
                'product_price' => $validatedData['product_price'],
                'product_rating' => $validatedData['product_rating'],
                'slug' => strtolower(str_replace(' ', '-', $validatedData['product_name'])),
                'images' => $imageUrls, // Update images
                'category_id' => $validatedData['category_id'],
            ]);

            // Return the updated product
            return response()->json([
                'message' => "{$product->product_name} updated successfully",
                'productData' => $product->load('category')
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], 500);
        }
    }
}
