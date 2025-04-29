<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\SystemPerformanceLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    // Logic to get today's total orders
    public function getTotalOrdersToday()
    {
        try {
            // Fetch today's total orders
            $totalOrders = Order::whereDate('created_at', now()->format('Y-m-d'))->count();

            // Calculate the total revenue from today's orders
            $totalRevenue = Order::whereDate('created_at', now()->format('Y-m-d'))->sum('total_price');

            return response()->json([
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching today\'s total orders.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to get total pending orders
    public function getTotalPendingOrders()
    {
        try {
            // Fetch the total pending orders
            $totalPendingOrders = Order::where('status', 'pending')->count();

            // Calculate the total revenue from pending orders
            $totalRevenue = Order::where('status', 'pending')->sum('total_price');

            return response()->json([
                'total_pending_orders' => $totalPendingOrders,
                'total_revenue' => $totalRevenue,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching the total pending orders.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to get products inventory status
    public function getProductsInventoryStatus()
    {
        try {
            // Get total products
            $totalProducts = Product::count();

            // Get total active products
            $totalActiveProducts = Product::where('is_active', true)->count();

            // Get total inactive products
            $totalInactiveProducts = Product::where('is_active', false)->count();

            return response()->json([
                'total_products' => $totalProducts,
                'total_active_products' => $totalActiveProducts,
                'total_inactive_products' => $totalInactiveProducts,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching the products inventory status.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to get total users with user role
    public function getTotalUsers()
    {
        try {
            // Fetch the total users with user role
            $totalUsers = User::where('role', 'user')->count();

            return response()->json([
                'total_users' => $totalUsers,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching the total users.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to get total revenue of the month
    public function getTotalRevenueOfMonth()
    {
        try {
            // Fetch the total revenue of the current month
            $totalRevenue = Order::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_price');

            return response()->json([
                'date_details' => now()->format('F Y'),
                'total_revenue' => $totalRevenue,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching the total revenue of the month.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to get user signup statistics in the last 3 months
    public function getUserSignupStatistics()
    {
        try {
            // Start date: 12 weeks ago from today (aligned to Monday)
            $startDate = Carbon::now()->subWeeks(12)->startOfWeek();

            // Initialize an array to hold the user signup data for each week
            $growthData = [];

            // Loop through the last 12 weeks
            for ($i = 0; $i < 12; $i++) {
                // Start of the current week
                $weekStart = $startDate->copy()->addWeeks($i);

                // Start of the next week (used as end boundary)
                $weekEnd = $weekStart->copy()->addWeek(); // Next week's start

                // Count users who signed up between weekStart and weekEnd
                $growth = User::whereBetween('created_at', [$weekStart, $weekEnd])->count();

                // Format the week as "dd-mm-yyyy"
                $growthData[] = [
                    'growth' => $growth,
                    'week' => $weekStart->format('d-m-Y'),
                ];
            }

            return response()->json([
                'growth_data' => $growthData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching user signup statistics.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to get monthly revenue statistics and compare with last month
    public function getMonthlyRevenueStatistics()
    {
        try {
            // Get today's date
            $today = Carbon::now();

            // Initialize an array to hold the revenue data for the last 8 weeks
            $revenue = [];

            // Loop through the last 8 weeks (Loop from 7 weeks ago to this week (for ascending order))
            for ($i = 7; $i >= 0; $i--) {
                // Get the start and end of the week
                $startOfWeek = $today->copy()->subWeeks($i)->startOfWeek();
                $endOfWeek = $today->copy()->subWeeks($i)->endOfWeek();


                // Calculate the revenue for the current week
                $weeklyRevenue = Order::whereBetween('created_at', [$startOfWeek, $endOfWeek])
                    ->sum('total_price');

                // Format the start of the week date as "dd-mm-yyyy"
                $revenue[] = [
                    'revenue' => $weeklyRevenue,
                    'week' => $startOfWeek->format('d-m-Y'),
                ];
            }

            return response()->json([
                'revenue' => $revenue,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching monthly revenue statistics.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to get last 10 orders
    public function getLatestOrders()
    {
        try {
            // Fetch the latest 8 orders
            $latestOrders = Order::with(['user:id,first_name,last_name,email'])
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            return response()->json([
                'orders' => $latestOrders,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching the latest orders.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to get system performance logs
    public function getSystemPerformanceLogs()
    {
        try {
            // Fetch latest 15 system performance logs
            $performanceLogs = SystemPerformanceLog::orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            // Format the logs to include user information
            foreach ($performanceLogs as $log) {
                $log->user = $log->user()->select('first_name', 'last_name', 'email')->first();
            }

            return response()->json([
                'performance_logs' => $performanceLogs,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching the system performance logs.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to get most viewed products
    public function getMostViewedProducts()
    {
        try {
            // Fetch the most viewed products (high to low)
            $mostViewedProducts = Product::orderBy('product_view', 'desc')
                ->take(9)
                ->get();

            // Initialize an array to hold the product data
            $productsData = [];

            // Prepare the response data
            foreach ($mostViewedProducts as $product) {
                $productsData[] = [
                    'name' => $product->product_name,
                    'viewCount' => $product->product_view,
                    'soldCount' => $product->product_sold,
                ];
            }


            return response()->json([
                'products_data' => $productsData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching the most viewed products.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic for global search
    public function globalSearch(Request $request)
    {
        try {
            // Validate the search term
            $request->validate([
                "query" => "required|string|min:1|max:255",
            ]);

            // Get the search term
            $searchTerm = $request->input('query');

            // Perform the search across multiple models
            $users = User::where('first_name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('email', 'LIKE', "%{$searchTerm}%")
                ->orWhere('phone_number', 'LIKE', "%{$searchTerm}%")
                ->get();

            $orders = Order::where('uuid', 'LIKE', "%{$searchTerm}%")
                ->orWhere('user_id', $searchTerm) // exact match
                ->orWhere('id', $searchTerm) // exact match
                ->with('user:id,first_name,last_name,email')
                ->get();

            $products = Product::where('product_name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('slug', 'LIKE', "%{$searchTerm}%")
                ->orWhere('product_barcode', 'LIKE', "%{$searchTerm}%")
                ->get();

            return response()->json([
                'message' => "Search results for: {$searchTerm}",
                'counts' => [
                    'users' => count($users),
                    'orders' => count($orders),
                    'products' => count($products),
                ],
                'users' => $users,
                'orders' => $orders,
                'products' => $products,
            ]);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while performing the global search.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }
}
