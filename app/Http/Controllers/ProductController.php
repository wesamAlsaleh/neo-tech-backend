<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{

    // Get all products
    public function getAllProducts(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'page' => 'integer|min:1', // Ensure the page number is an integer and greater than 0
            ]);

            // Get the page number from the request eg. /best-selling-products?page=1
            $request->query('page') ?? 1; // Default to page 1 if not provided or invalid

            // Get all products with pagination
            $products = Product::paginate(10); // get all products with pagination (10 products per page)

            // If no products were found, return an empty array
            if ($products->isEmpty()) {
                return response()->json([
                    'message' => 'No products found',
                    'products' => []
                ], 200);
            }

            // Return the products
            return response()->json([
                'message' => 'Products retrieved successfully',
                'products' => $products->items(), // Return only the products on the current page
                'pagination' => [
                    'current_page' => $products->currentPage(), // The current page number
                    'total_pages' => $products->lastPage(), // The last page number
                ]
            ], 200);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            Log::error('Error fetching products: ' . $e->getMessage());

            return response()->json([
                'message' => 'Something went wrong while fetching products. Please try again later.',
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Get a single product by id
    public function getProductById(String $id): JsonResponse
    {
        try {
            // Attempt to find product or throw 404
            $product = Product::findOrFail($id);

            // Ensure images are an array before attempting to map over them
            $images = is_array($product->images) ? $product->images : [];

            // Increment the view count
            $product->increment('product_view');

            // // Check if the product has images
            // if ($product->images) {
            //     // Prepend the full URL to each image path in the images array
            //     $product->images = array_map(function ($image) {
            //         // Ensure the image path starts with 'storage/' for local assets
            //         if (strpos($image, 'storage/') === false) {
            //             return asset('storage/' . $image); // Add 'storage/' if it's missing
            //         }

            //         return $image; // If 'storage/' is already in the path, leave it as is
            //     }, $product->images);
            // }

            // Prepend full URL to images
            // $product->images = array_map(function ($image) {
            //     return asset('storage/' . ltrim($image, '/')); // Ensure path consistency
            // }, $images);

            // Get the category of the product
            $product->category_id = Category::findOrFail($product->category_id);

            // Return the product with images
            return response()->json([
                'message' => 'Product found',
                'product' => $product
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'userMessage' => 'Product not found',
                'developerMessage' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            Log::error('Error fetching product: ' . $e->getMessage());

            return response()->json([
                'userMessage' => 'Something went wrong while retrieving the product.',
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Create a new product
    public function createProduct(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'product_name' => 'required|string|max:255|unique:products,product_name',
                'product_description' => 'nullable|string',
                'product_price' => 'required|numeric|min:0',
                'product_rating' => 'nullable|numeric|min:0|max:5',
                'product_stock' => 'nullable|integer|min:0',
                'product_sold' => 'nullable|integer|min:0',
                'product_view' => 'nullable|integer|min:0',
                'product_barcode' => 'required|string|unique:products,product_barcode|max:50',
                'product_images' => 'nullable|array',
                'product_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Max 2MB per image
                'category_id' => 'required|exists:categories,id',
                'is_active' => 'nullable|boolean',
            ]);

            // Initialize an empty array to store the image paths as URLs (public)
            $imageUrls = [];

            // Create the slug from the product name
            $slug = strtolower(str_replace(' ', '-', $validatedData['product_name']));

            // Check for existing slug and append a number if necessary
            $slugBase = $slug;
            $counter = 1;
            while (Product::where('slug', $slug)->exists()) {
                $slug = $slugBase . '-' . $counter++;
            }

            // Check if images are provided then iterate over them to store in storage
            if ($request->hasFile('product_images')) {
                // Loop through each image and store it in storage
                foreach ($request->file('product_images') as $image) {
                    try {
                        // Generate a unique file name e.g. 612f7b7b618f4.jpg
                        $imageName = uniqid() . '.' . $image->getClientOriginalExtension();

                        // Create a folder name based on the product name
                        $folderName = strtolower(str_replace(' ', '_', $validatedData['product_name']));

                        // Store the image in storage/app/public/images/products_images/{productName}
                        $path = $image->storeAs("images/products_images/{$folderName}", $imageName, 'public');

                        // Push the public URL to the array
                        $imageUrls[] = asset('storage/' . $path);
                    } catch (\Exception $e) {
                        return response()->json(['message' => 'Failed to upload one or more images.'], 500);
                    }
                }
            }


            // Create the product
            $product = Product::create([
                'product_name' => $validatedData['product_name'],
                'product_description' => $validatedData['product_description'],
                'product_price' => $validatedData['product_price'],
                'product_rating' => $validatedData['product_rating'] ?? 0, // 'product_rating' => by default 0,
                'product_stock' => $validatedData['product_stock'] ?? 0,
                'product_sold' => $validatedData['product_sold'] ?? 0,
                'product_view' => $validatedData['product_view'] ?? 0,
                'product_barcode' => $validatedData['product_barcode'],
                'slug' => $slug, // 'slug' => generated from product name,
                'images' => $imageUrls, // Store as JSON
                'category_id' => $validatedData['category_id'],
                'is_active' => $validatedData['is_active'] ?? false, // 'is_active' => by default false,
            ]);



            // Return the product
            return response()->json([
                'message' => "{$product->product_name} created successfully",
                'productData' => $product->load('category')
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => explode(':', $e->getMessage())[1], // Get the error message without "The name field is required."
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json(
                [
                    'message' => $e->getMessage()
                ],
                500
            );
        }
    }

    // Update a product by id
    public function updateProductById(Request $request, String $id): JsonResponse
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'product_name' => 'required|string|max:255',
                'product_description' => 'nullable|string',
                'product_price' => 'nullable|numeric|min:0',
                'product_rating' => 'nullable|numeric|min:0|max:5',
                'product_stock' => 'nullable|integer|min:0',
                'product_sold' => 'nullable|integer|min:0',
                'product_view' => 'nullable|integer|min:0',
                'product_barcode' => 'nullable|string|max:50',
                'product_images' => 'nullable|array',
                'product_images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Max 2MB per image
                'category_id' => 'required|exists:categories,id',
                'is_active' => 'nullable|boolean',
            ]);

            // Find the product
            $product = Product::findOrFail($id);

            // Initialize an empty array to store the image URLs
            $imageUrls = $product->images ?? []; // Keep existing images if not updated

            // If the name is updated, update the slug
            if ($validatedData['product_name'] !== $product->product_name) {
                // Create the slug from the product name
                $slug = strtolower(str_replace(' ', '-', $validatedData['product_name']));

                // Check for existing slug and append a number if necessary
                $slugBase = $slug;
                $counter = 1;
                while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                    $slug = $slugBase . '-' . $counter++;
                }
            } else {
                $slug = $product->slug; // Preserve existing slug
            }

            // If new images are uploaded, replace the old ones
            if ($request->hasFile('product_images')) {
                try {
                    // Delete old images from storage
                    foreach ($product->images as $oldImage) {
                        // Get the path of the image
                        $oldImagePath = str_replace(asset('storage/'), '', $oldImage);

                        // Delete the image from storage
                        Storage::disk('public')->delete($oldImagePath);
                    }

                    $imageUrls = []; // Reset images

                    // Loop through new images and store them
                    foreach ($request->file('product_images') as $image) {
                        // Generate a unique file name for the image e.g. 612f7b7b618f4.jpg
                        $imageName = uniqid() . '.' . $image->getClientOriginalExtension();

                        // Create a folder name based on the product name (whether updated or not)
                        $folderName = strtolower(str_replace(' ', '_', $validatedData['product_name']));

                        // Store the image in storage/app/public/products_images/{productName}
                        $path = $image->storeAs("images/products_images/{$folderName}", $imageName, 'public');

                        // Push the public URL to the array
                        $imageUrls[] = asset('storage/' . $path);
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to upload images: ' . $e->getMessage());

                    return response()->json([
                        'status' => false,
                        'message' => 'Failed to upload one or more images.',
                        'errors' => ['product_images' => ['Failed to upload images.']]
                    ], 500);
                }
            }

            // Update the product details
            $product->update([
                'product_name' => $validatedData['product_name'],
                'product_description' => $validatedData['product_description'],
                'product_price' => $validatedData['product_price'],
                'product_rating' => $validatedData['product_rating'] ?? 0,
                'product_stock' => $validatedData['product_stock'] ?? 0,
                'product_sold' => $validatedData['product_sold'] ?? 0,
                'product_view' => $validatedData['product_view'] ?? 0,
                'product_barcode' => $validatedData['product_barcode'] ?? $product->product_barcode,
                'slug' => $slug, // Generated slug
                'images' => $imageUrls, // Updated images
                'category_id' => $validatedData['category_id'],
                'is_active' => $validatedData['is_active'] ?? $product->is_active, // Preserve existing if not updated
            ]);

            // Return the updated product
            return response()->json([
                'status' => true,
                'message' => "{$product->product_name} updated successfully",
                'productData' => $product->load('category')
            ], 200);
        } catch (ValidationException $e) {
            // Return validation errors as JSON
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'error' => $e->errors()
            ], 422);
        } catch (ModelNotFoundException $e) {
            // Handle product not found
            return response()->json([
                'status' => false,
                'message' => 'Product not found',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            // Return a generic error message
            return response()->json([
                'status' => false,
                'message' => 'Failed to update product due to an error in the server',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Soft delete a product by id
    public function deleteProductById(String $id): JsonResponse
    {
        try {
            // Find the product, even if it is soft deleted
            $product = Product::findOrFail($id);

            // Check if the product exists
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            // Soft delete the product (sets 'deleted_at' to the current timestamp)
            $product->delete();

            // Return a success message
            return response()->json([
                'message' => "{$product->product_name} deleted successfully"
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // Restore a soft-deleted product by id
    public function restoreProductById(String $id): JsonResponse
    {
        try {
            // Find the soft-deleted product
            $product = Product::withTrashed()->find($id);

            // Check if the product exists, even if it's deleted
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            // Restore the product
            $product->restore();

            // Return a success message
            return response()->json([
                'message' => "{$product->product_name} restored successfully"
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // Get all deleted products (soft deleted)
    public function getDeletedProducts(): JsonResponse
    {
        try {
            // Get all soft-deleted products
            $products = Product::onlyTrashed()->get();

            // Check if any products were found, if not, return an empty array
            if ($products->isEmpty()) {
                return response()->json([], 200);
            }

            // Return the products
            return response()->json(
                [
                    'message' => 'Deleted products retrieved successfully',
                    'products' => $products
                ],
                200
            );
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // Put a product on sale
    public function putProductOnSale(String $id, Request $request): JsonResponse
    {
        try {
            // Find the product
            $product = Product::findOrFail($id);

            // Validate the request
            $validated = $request->validate([
                'discount' => 'required|numeric|min:0|max:100', // Ensure discount is between 0 and 100%
                'sale_end' => 'required|date|after:today', // Ensure the sale end date is in the future
            ]);

            // If the product is already on sale, return an error
            if ($product->onSale) {
                return response()->json([
                    'message' => "{$product->product_name} is already on sale, the discount is {$product->discount}% and the sale ends on {$product->sale_end}",
                    'developerMessage' => 'PRODUCT_ALREADY_ON_SALE',
                ], 400);
            }

            // Calculate the product price after discount
            $product->product_price_after_discount = $product->product_price - ($product->product_price * ($validated['discount'] / 100));

            // Put the product on sale
            $product->update([
                'onSale' => true,
                'discount' => $validated['discount'],
                'sale_start' => now(), // Sale starts immediately
                'sale_end' => $validated['sale_end'],
            ]);

            // Return the updated product
            return response()->json([
                'message' => "{$product->product_name} is now on sale, the discount is {$product->discount}% and the sale starts on {$product->sale_start} and the sale ends on {$product->sale_end}, the sale duration is {$product->sale_duration} days.",
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                // 'message' => explode(':', $e->getMessage())[1], // Get the error message without "The name field is required."
                'message' => 'Validation failed',
                'developerMessage' => $e->getMessage()
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found',
                'developerMessage' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Failed to put product on sale",
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Remove a product from sale
    public function removeProductFromSale(String $id): JsonResponse
    {
        try {
            // Find the product
            $product = Product::findOrFail($id);

            // If the product is not on sale, return an error
            if (!$product->onSale) {
                return response()->json([
                    'message' => "{$product->product_name} is not on sale, hence cannot be removed from sale",
                    'developerMessage' => 'PRODUCT_NOT_ON_SALE',
                ], 400);
            }

            // Remove the product from sale
            $product->update([
                'onSale' => false,
                'discount' => 0,
                'sale_start' => null,
                'sale_end' => null,
                'product_price_after_discount' => 0,
            ]);

            // Return the updated product
            return response()->json([
                'message' => "{$product->product_name} is no longer on sale, the discount has been removed",
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found',
                'developerMessage' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Failed to remove product from sale",
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Remove all products from sale
    public function removeAllProductsFromSale(): JsonResponse
    {
        try {
            // Get all products on sale
            $products = Product::where('onSale', true)->get();

            // If no products are on sale, return an error
            if ($products->isEmpty()) {
                return response()->json([
                    'message' => 'No products are currently on sale',
                    'developerMessage' => 'NO_PRODUCTS_ON_SALE',
                ], 400);
            }

            // Remove all products from sale
            $products->each(function ($product) {
                $product->update([
                    'onSale' => false,
                    'discount' => 0,
                    'sale_start' => null,
                    'sale_end' => null,
                    'product_price_after_discount' => 0,
                ]);
            });

            // Return a success message
            return response()->json([
                'message' => 'All products have been removed from sale',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Failed to remove all products from sale",
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Get all products on sale with pagination
    public function getProductsOnSale(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'page' => 'integer|min:1', // Ensure the page number is an integer and greater than 0
            ]);

            // Get the page number from the request eg. /best-selling-products?page=1
            $request->query('page') ?? 1; // Default to page 1 if not provided or invalid

            // Get all products on sale
            $products = Product::where('onSale', true)->paginate(9); // get all products on sale with pagination (9 products per page)

            // Check if any products were found
            if ($products->isEmpty()) {
                return response()->json([
                    'message' => 'No products on sale',
                    'products' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'total_pages' => 1,
                        'total_products_onSale' => 0,
                    ]
                ], 200);
            }

            // Return the products
            return response()->json([
                'message' => 'Products on sale retrieved successfully',
                'products' => $products->items(), // Return only the products on the current page
                'pagination' => [
                    'current_page' => $products->currentPage(), // The current page number
                    'total_pages' => $products->lastPage(), // The last page number
                    'total_products_onSale' => $products->total(), // The total number of products on sale
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Failed to fetch products on sale",
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Search for products by name (case-insensitive for search bar)
    public function searchProductsByName(String $productName): JsonResponse
    {
        try {
            // Search for products where the name contains the search term (case-insensitive)
            $products = Product::where('product_name', 'LIKE', "%{$productName}%")->get(); // get all products that match the search term (case-insensitive)

            // Check if products were found
            if ($products->isEmpty()) {
                return response()->json(['message' => 'Product not found'], 404);
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

                    // If it already includes 'storage/', return the asset directly
                    return asset($image);
                }, $product->images) : [];

                return $product;
            });

            // Return the products
            return response()->json($productsWithImages, 200);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Product not found'], 404);
        } catch (QueryException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
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
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'No products found'], 404);
        } catch (QueryException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
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
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'No products found'], 404);
        } catch (QueryException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // Search for products by status
    public function searchProductsByStatus(bool $status): JsonResponse
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
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'No products found'], 404);
        } catch (QueryException $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // Search for products by slug
    public function searchProductsBySlug($slug): JsonResponse
    {
        try {
            // Find a single product by slug
            $product = Product::where('slug', $slug)->first();

            // If product was not found, return a 404 response
            if (!$product) {
                return response()->json([
                    'userMessage' => "Product not found",
                    'developerMessage' => 'No product found with the given slug'
                ], 404);
            }

            // Increment view count
            $product->increment('product_view');

            // Check if the product has images and prepend the full URL
            // $product->images = $product->images ? array_map(function ($image) {
            //     return asset('storage/' . $image);
            // }, $product->images) : [];

            // Get the category of the product
            $product->category_id = Category::findOrFail($product->category_id);

            // Return the product
            return response()->json([
                'message' => 'Product found',
                'product' => $product,
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Failed to search for the product',
                'developerMessage' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Failed to search for the product",
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }

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
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    // Get all best selling products with pagination
    public function getBestSellingProducts(Request $request): JsonResponse
    {
        try {
            // Validate the request
            $request->validate([
                'page' => 'integer|min:1', // Ensure the page number is an integer and greater than 0
            ]);

            // Get the page number from the request eg. /best-selling-products?page=1
            $request->query('page') ?? 1; // Default to page 1 if not provided or invalid

            // Get the best selling products with pagination based on the page number
            $products = Product::where('is_active', true)
                ->orderBy('product_sold', 'desc')
                ->paginate(8);

            // Check if products were found
            if ($products->isEmpty()) {
                return response()->json([
                    'message' => 'No products found',
                    'products' => [],
                ], 200);
            }

            // Return the products along with pagination info
            return response()->json([
                'message' => 'Best selling products retrieved successfully',
                'products' => $products->items(),  // The current page products
                'pagination' => [
                    'current_page' => $products->currentPage(),
                    'total_pages' => $products->lastPage(),
                    'total_items' => $products->total(),
                ]
            ], 200);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Failed to fetch best selling products',
                'developerMessage' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch best selling products',
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Get latest products
    public function getLatestProducts(): JsonResponse
    {
        try {
            // Get the top 10 latest products
            $products = Product::orderBy('created_at', 'desc')->take(10)->get();

            // Check if products were found
            if ($products->isEmpty()) {
                return response()->json(['message' => 'No products found'], 404);
            }

            // Return the products
            return response()->json([
                'message' => 'Latest products retrieved successfully',
                'products' => $products
            ], 200);
        } catch (ModelNotFoundException) {
            return response()->json([
                'message' => 'No products found',
                'developerMessage' => ''
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Failed to fetch latest products',
                'developerMessage' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch latest products',
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Get explore products (8 random products)
    public function getExploreProducts(Request $request): JsonResponse
    {
        try {
            // Get 18 random active products
            $products = Product::where('is_active', true)
                ->inRandomOrder()
                ->limit(18)
                ->get();

            // Check if products were found
            if ($products->isEmpty()) {
                return response()->json([
                    'message' => 'No products found',
                    'products' => []
                ], 404);
            }

            // Return the products
            return response()->json([
                'message' => 'Explore products retrieved successfully',
                'products' => $products,

            ], 200);
        } catch (ModelNotFoundException) {
            return response()->json([
                'message' => 'No products found',
                'developerMessage' => ''
            ], 404);
        } catch (QueryException $e) {
            return response()->json([
                'message' => 'Failed to fetch explore products',
                'developerMessage' => $e->getMessage()
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch explore products',
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Increment the product rating (stars system)
    public function putRating(Request $request, String $id): JsonResponse
    {
        try {
            // Find the product
            $product = Product::findOrFail($id);

            // Validate the request
            $validated = $request->validate([
                'rating' => 'required|numeric|min:1|max:5', // Ensure rating is between 1 and 5
            ]);

            // Get the current rating as average of all ratings and increment the rating
            $newRating = ($product->product_rating + $validated['rating']) / 2;

            // Update the product rating
            $product->update([
                'product_rating' => $newRating
            ]);

            // Return the updated product
            return response()->json([
                'message' => "{$product->product_name} rating incremented successfully",
                'product' => $product
            ], 200);
        } catch (ModelNotFoundException) {
            return response()->json([
                'message' => 'Product not found',
                'devMessage' => 'PRODUCT_NOT_FOUND'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => "Failed to increment {$product->product_name} rating",
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }
}
