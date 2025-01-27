<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
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
    public function getProductById(String $id): JsonResponse
    {
        try {
            // Get the product
            $product = Product::find($id);

            // Check if the product exists
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            // Check if the product has images
            if ($product->images) {
                // Prepend the full URL to each image path in the images array
                $product->images = array_map(function ($image) {
                    // Ensure the image path starts with 'storage/' for local assets
                    if (strpos($image, 'storage/') === false) {
                        return asset('storage/' . $image); // Add 'storage/' if it's missing
                    }

                    return $image; // If 'storage/' is already in the path, leave it as is
                }, $product->images);
            }

            // Return the product with images
            return response()->json([
                'message' => 'Product found',
                'product' => $product
            ], 200);
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
    public function updateProductById(Request $request, String $id): JsonResponse
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

    // Delete a product by id
    public function deleteProductById(String $id): JsonResponse
    {
        try {
            // Find the product
            $product = Product::findOrFail($id);

            // Check if the product exists
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            // Check if the product has images
            if (!empty($product->images)) {
                // Delete the images from storage
                foreach ($product->images as $image) {
                    // Get the relative path of the image
                    $imagePath = str_replace(asset('storage/'), '', $image);

                    // Delete the image from storage
                    Storage::disk('public')->delete($imagePath);
                }
            }

            // Delete the product
            $product->delete();

            // Return a success message
            return response()->json([
                'message' => "{$product->product_name} deleted successfully"
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], 500);
        }
    }

    // Search for products by name
    public function searchProductsByName(String $productName): JsonResponse
    {
        try {
            // Search for products where the name contains the search term (case-insensitive)
            $products = Product::where('product_name', 'LIKE', "%{$productName}%")->get(); // get all products that match the search term (case-insensitive)

            // Check if products were found
            if ($products->isEmpty()) {
                return response()->json(['message' => 'No products found'], 404);
            }

            /**
             * Transform the products to include full image URLs
             * This is done by mapping over the products and appending the full URL to each image path in the images array
             * If the product has no images, an empty array is returned
             * The products are then returned as a JSON response
             */
            $productsWithImages = $products->map(function ($product) {
                // Ensure images are properly concatenated
                $product->images = $product->images ? array_map(function ($image) {
                    // Check if the image path already contains 'storage/' to avoid duplication
                    if (strpos($image, 'storage/') === false) {
                        return asset('storage/' . $image);  // Only prepend 'storage/' if it is not already included
                    }
                    return asset($image);  // If it already includes 'storage/', return the asset directly
                }, $product->images) : [];

                return $product;
            });

            // Return the products
            return response()->json($productsWithImages, 200);
        } catch (\Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], 500);
        }
    }

    // Search for products by category name
    public function searchProductsByCategory(String $categoryName): JsonResponse
    {
        try {
            // Find the category by name (assuming 'name' column exists in the categories table)
            $category = Category::where('name', 'LIKE', "%{$categoryName}%")->first(); // get the first category that matches the search term

            // Check if the category exists
            if (!$category) {
                return response()->json(['message' => 'Category not found'], 404);
            }

            // Get the products in the category
            $products = Product::where('category_id', $category->id)->get();

            // Check if no products were found
            if ($products->isEmpty()) {
                return response()->json(['message' => 'No products found in this category'], 404);
            }

            // Transform products to include full image URLs
            $productsWithImages = $products->map(function ($product) {
                // Check if the product has images, if so, prepend the URL to each image path in the array, else return an empty array
                $product->images = $product->images ?
                    array_map(function ($image) {
                        return asset('storage/' . $image); // Ensure it has the correct URL
                    }, $product->images) : [];

                // Return the product
                return $product;
            });

            // Return the products
            return response()->json([
                'message' => 'Products found',
                'category' => $category->name,
                'products' => $productsWithImages
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], 500);
        }
    }

    // Search for products by rating
    public function searchProductsByRating($rating): JsonResponse
    {
        try {
            // Check if the rating is within the range 0-5
            if ($rating < 0 || $rating > 5) {
                return response()->json(['message' => 'Rating must be between 0 and 5'], 400);
            }

            // Search for products where the rating is equal to the search term
            $products = Product::where('product_rating', $rating)->get(); // get all products that match the search term

            // Check if products were found
            if ($products->isEmpty()) {
                return response()->json(['message' => 'No products found'], 404);
            }

            // Transform the products to include full image URLs
            $productsWithImages = $products->map(function ($product) {
                // Check if the product has images, if so, prepend the URL to each image path in the array, else return an empty array
                $product->images = $product->images ?
                    array_map(function ($image) {
                        return asset('storage/' . $image); // Ensure it has the correct URL
                    }, $product->images) : [];

                // Return the product
                return $product;
            });

            // Return the products
            return response()->json($productsWithImages, 200);
        } catch (\Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], 500);
        }
    }

    // Search for products by price range
    public function searchProductsByPriceRange($minPrice, $maxPrice): JsonResponse
    {
        try {
            // Search for products where the price is within the range
            $products = Product::whereBetween('product_price', [$minPrice, $maxPrice])->get(); // get all products that match the search term

            // Check if products were found
            if ($products->isEmpty()) {
                return response()->json(['message' => 'No products found'], 404);
            }

            // Transform the products to include full image URLs
            $productsWithImages = $products->map(function ($product) {
                // Check if the product has images, if so, prepend the URL to each image path in the array, else return an empty array
                $product->images = $product->images ?
                    array_map(function ($image) {
                        return asset('storage/' . $image); // Ensure it has the correct URL
                    }, $product->images) : [];

                // Return the product
                return $product;
            });

            // Return the products
            return response()->json($productsWithImages, 200);
        } catch (\Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], 500);
        }
    }

    // Search for products by availability
    public function searchProductsByAvailability($availability): JsonResponse
    {
        try {
            // Search for products where the availability is equal to the search term
            $products = Product::where('in_stock', $availability)->get(); // get all products that match the search term

            // Check if products were found
            if ($products->isEmpty()) {
                return response()->json(['message' => 'No products found'], 404);
            }

            // Transform the products to include full image URLs
            $productsWithImages = $products->map(function ($product) {
                // Check if the product has images, if so, prepend the URL to each image path in the array, else return an empty array
                $product->images = $product->images ?
                    array_map(function ($image) {
                        return asset('storage/' . $image); // Ensure it has the correct URL
                    }, $product->images) : [];

                // Return the product
                return $product;
            });

            // Return the products and the number of products found
            return response()->json($productsWithImages, 200);
        } catch (\Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], 500);
        }
    }

    // Search for products by status
    public function searchProductsByStatus($status): JsonResponse
    {
        try {
            // Search for products where the status is equal to the search term
            $products = Product::where('is_active', $status)->get(); // get all products that match the search term

            // Check if products were found
            if ($products->isEmpty()) {
                return response()->json(['message' => 'No products found'], 404);
            }

            // Transform the products to include full image URLs
            $productsWithImages = $products->map(function ($product) {
                // Check if the product has images, if so, prepend the URL to each image path in the array, else return an empty array
                $product->images = $product->images ?
                    array_map(function ($image) {
                        return asset('storage/' . $image); // Ensure it has the correct URL
                    }, $product->images) : [];

                // Return the product
                return $product;
            });

            // Return the products
            return response()->json($productsWithImages, 200);
        } catch (\Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], 500);
        }
    }

    // Search for products by slug
    public function searchProductsBySlug($slug): JsonResponse
    {
        try {
            // Search for products where the slug is equal to the search term
            $products = Product::where('slug', $slug)->get(); // get all products that match the search term

            // Check if products were found
            if ($products->isEmpty()) {
                return response()->json(['message' => 'No products found'], 404);
            }

            // Transform the products to include full image URLs
            $productsWithImages = $products->map(function ($product) {
                // Check if the product has images, if so, prepend the URL to each image path in the array, else return an empty array
                $product->images = $product->images ?
                    array_map(function ($image) {
                        return asset('storage/' . $image); // Ensure it has the correct URL
                    }, $product->images) : [];

                // Return the product
                return $product;
            });

            // Return the products
            return response()->json($productsWithImages, 200);
        } catch (\Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], 500);
        }
    }

    // TODO: Search for products by category and price range


    // Toggle the status of a product by id
    public function toggleProductStatusById(String $id): JsonResponse
    {
        try {
            // Find the product
            $product = Product::findOrFail($id);

            // Check if the product exists
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            // Toggle the status of the product
            $product->update([
                'is_active' => !$product->is_active
            ]);

            // Return the updated product
            return response()->json([
                'message' => "{$product->product_name} is now " . ($product->is_active ? 'active' : 'inactive'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], 500);
        }
    }

    // Toggle the availability of a product by id
    public function toggleProductAvailabilityById(String $id): JsonResponse
    {
        try {
            // Find the product
            $product = Product::findOrFail($id);

            // Check if the product exists
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            // Toggle the availability of the product
            $product->update([
                'in_stock' => !$product->in_stock
            ]);

            // Return the updated product
            return response()->json([
                'message' => "{$product->product_name} is now " . ($product->in_stock ? 'available' : 'out of stock'),
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['errorMessage' => $e->getMessage()], 500);
        }
    }
}
