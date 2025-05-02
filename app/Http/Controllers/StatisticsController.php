<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class StatisticsController extends Controller
{
    // Logic to get all products with their statistics with pagination
    public function getAllProductsWithStatistics(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100',
            ]);

            // Get the pagination parameters
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);

            // Get all products from the database
            $products = Product::orderBy('product_view', 'desc')
                ->select([
                    'product_name',
                    'product_rating',
                    'product_sold',
                    'product_view',
                    'is_active',
                ])
                ->paginate(
                    $perPage, // Number of items per page
                    ['*'], // Get all columns
                    '', // Custom pagination page name
                    $page // Current page
                );


            return response()->json([
                'message' => 'Products fetched successfully',
                'products' => $products,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch products',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }
}
