<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class HomePageController extends Controller
{
    // Home page products
    public function getHomePageProducts(): JsonResponse
    {
        try {
            // Fetch the categories 6 each for the home page
            $categories = Category::where('is_active', true)
                ->with('products') // Eager load the products for each category
                ->limit(6)
                ->get();

            // Fetch the best selling products (most sold)
            $bestSellingProducts = Product::where('is_active', true)
                ->orderBy('product_sold', 'desc')
                ->limit(10) // Limit to 10 products only
                ->get();

            // Fetch the explore products (most viewed)
            $exploreProducts = Product::where('is_active', true)
                ->orderBy('product_view', 'desc')
                ->limit(8) // Limit to 8 products only
                ->get();

            // Fetch the latest products
            $latestProducts = Product::where('is_active', true)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();

            // TODO: Fetch the products based on the sales




            // Return the products
            return response()->json([
                'categories' => $categories,
                'bestSellingProducts' => $bestSellingProducts,
                'exploreProducts' => $exploreProducts,
                'latestProducts' => $latestProducts
            ], 200);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            Log::error('Error fetching products: ' . $e->getMessage());

            return response()->json([
                'message' => 'Something went wrong while fetching the data',
                'developerMessage' => $e->getMessage()
            ], 500);
        }
    }
}
