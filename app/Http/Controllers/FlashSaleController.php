<?php

namespace App\Http\Controllers;

use App\Models\FlashSale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class FlashSaleController extends Controller
{
    // Get all flash sales in the database
    public function index()
    {
        return FlashSale::all();
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
                // Find the product
                $product = Product::find($productId);

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

            // Create the flash sale
            FlashSale::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'products' => $validated['products'],
            ]);

            return response()->json([
                'message' => 'Flash sale created',
                'duration' => Carbon::parse($validated['start_date'])->diffInDays($validated['end_date'])
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Flash sale creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get a single flash sale by ID
    public function show(String $id)
    {
        return FlashSale::findOrFail($id);
    }
}
