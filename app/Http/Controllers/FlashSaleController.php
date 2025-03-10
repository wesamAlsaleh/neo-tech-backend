<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FlashSaleController extends Controller
{
    // Get all flash sales
    public function index()
    {
        try {
            // Fetch all flash sales with their products (products is a JSON array of product IDs) (max 5 flash sales in the shop!)
            $flashSales = FlashSale::orderBy('created_at', 'desc')->get();

            // If there are no flash sales return an empty array
            if ($flashSales->isEmpty()) {
                return response()->json([
                    'message' => 'No flash sales found',
                    'flashSales' => []
                ], 200);
            }

            // Loop through the flash sales and attach the products
            foreach ($flashSales as $flashSale) {
                $flashSale->products = Product::whereIn('id', $flashSale->products)->get();
            }

            return response()->json([
                'message' => 'Flash sales retrieved',
                'flashSales' => $flashSales,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Flash sales retrieval failed',
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Create a new flash sale
    public function store(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'name' => 'required|string',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'products' => 'required|array', // Array of product IDs
                'products.*' => 'required|integer|exists:products,id', // Each product ID must exist in the products table
            ]);

            // Check if the product is active and on sale
            foreach ($validated['products'] as $productId) {
                // Find the product or throw an exception
                $product = Product::findOrFail($productId);

                // If the product is not active return an error
                if (!$product->is_active) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'developerMessage' => "{$product->product_name} is not active"
                    ], 422);
                }

                // If the product is not on sale return an error
                if (!$product->onSale) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'developerMessage' => "{$product->product_name} is not on sale"
                    ], 422);
                }
            }

            // Check if the dates is in the past
            if (Carbon::parse($validated['start_date'])->isPast() || Carbon::parse($validated['end_date'])->isPast()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'developerMessage' => 'DATE_IS_PAST'
                ], 422);
            }

            // Check if the dates are the same
            if (Carbon::parse($validated['start_date'])->isSameDay(Carbon::parse($validated['end_date']))) {
                return response()->json([
                    'message' => 'Validation failed',
                    'developerMessage' => 'DATES_ARE_SAME'
                ], 422);
            }

            // Create the flash sale
            FlashSale::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'products' => $validated['products'],
            ]);

            // Calculate the duration of the flash sale
            $duration = Carbon::parse($validated['start_date'])->diffInDays($validated['end_date']);
            $duration_format = $duration >= 1 ? 'days' : '(less than a day)';


            return response()->json([
                'message' => "$validated[name] flash sale created successfully",
                'duration' => 'Available for ' . $duration . " " . $duration_format
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'developerMessage' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Flash sale creation failed',
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Get a single flash sale by ID
    public function show(String $id)
    {
        try {
            // Find the flash sale or throw an exception
            $flashSale = FlashSale::findOrFail($id);

            // Attach the products
            $flashSale->products = Product::whereIn('id', $flashSale->products)->get();

            return response()->json([
                'message' => 'Flash sale retrieved',
                'flashSale' => $flashSale
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Flash sale retrieval failed',
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Delete a flash sale by ID
    public function destroy(String $id)
    {
        try {
            // Find the flash sale or throw an exception
            $flashSale = FlashSale::findOrFail($id);

            // Delete the flash sale
            $flashSale->delete();

            return response()->json([
                'message' => "$flashSale->name flash sale deleted successfully"
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Flash sale deletion failed',
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Update a flash sale by ID
    public function update(Request $request, String $id)
    {
        try {
            // Find the flash sale or throw an exception
            $flashSale = FlashSale::findOrFail($id);

            // Validate the request
            $validated = $request->validate([
                'name' => 'required|string',
                'description' => 'nullable|string',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'products' => 'required|array', // Array of product IDs
                'products.*' => 'required|integer|exists:products,id', // Each product ID must exist in the products table
            ]);

            // Check if the product is active and on sale
            foreach ($validated['products'] as $productId) {
                // Find the product or throw an exception
                $product = Product::findOrFail($productId);

                // If the product is not active return an error
                if (!$product->is_active) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'developerMessage' => "{$product->product_name} is not active"
                    ], 422);
                }

                // If the product is not on sale return an error
                if (!$product->onSale) {
                    return response()->json([
                        'message' => 'Validation failed',
                        'developerMessage' => "{$product->product_name} is not on sale"
                    ], 422);
                }
            }

            // Check if the dates is in the past
            if (Carbon::parse($validated['start_date'])->isPast() || Carbon::parse($validated['end_date'])->isPast()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'developerMessage' => 'DATE_IS_PAST'
                ], 422);
            }

            // Check if the dates are the same
            if (Carbon::parse($validated['start_date'])->isSameDay(Carbon::parse($validated['end_date']))) {
                return response()->json([
                    'message' => 'Validation failed',
                    'developerMessage' => 'DATES_ARE_SAME'
                ], 422);
            }

            // Update the flash sale
            $flashSale->update([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'products' => $validated['products'],
            ]);

            // Calculate the duration of the flash sale
            $duration = Carbon::parse($validated['start_date'])->diffInDays($validated['end_date']);
            $duration_format = $duration >= 1 ? 'days' : '(less than a day)';

            return response()->json([
                'message' => "$validated[name] flash sale updated successfully",
                'duration' => 'Available for ' . $duration . " " . $duration_format
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'developerMessage' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Flash sale update failed',
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Toggle flash sale status by ID
    public function toggleFlashSaleStatusById(String $id)
    {
        // Find the flash sale or throw an exception
        $flashSale = FlashSale::findOrFail($id);

        // Check if the

        $flashSale->is_active = !$flashSale->is_active;
        $flashSale->save();

        return response()->json([
            'message' => 'Flash sale status toggled'
        ], 200);
    }
}
