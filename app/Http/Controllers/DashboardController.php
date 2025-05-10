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

    // Logic to get user signup statistics (chart)
    public function getMonthlyUserSignupStatistics()
    {
        try {
            // Initialize an array to hold the user signup data for each week
            $growthData = [];

            // Loop through the last 12 weeks
            for ($i = 0; $i < 6; $i++) {
                // Get the current date and subtract months
                $date = Carbon::now()->subMonths($i); // 0 - 5, means current month and previous 6 months

                $startDate = $date->copy()->startOfMonth(); // Start of the month
                $endDate = $date->copy()->endOfMonth(); // End of the month

                // Count users who signed up between weekStart and weekEnd
                $growth = User::whereBetween('created_at', [$startDate, $endDate])->count();

                // Format the week as "dd-mm-yyyy"
                $growthData[] = [
                    'growth' => $growth,
                    'month' => $date->format('F Y'), // e.g. "January 2023"
                ];
            }

            // Reverse the array so it shows from oldest to newest (current month first, on the right)
            $growthData = array_reverse($growthData);

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

    // Logic to get monthly revenue statistics (chart)
    public function getMonthlyRevenueStatistics()
    {
        try {
            // Initialize an array to hold the revenue data
            $revenue = [];

            // Loop from 0 to 11 for this month and previous 5 months
            for ($i = 0; $i < 6; $i++) {
                // Get the start and end of the month safely
                $date = Carbon::now()->subMonths($i); // 0 - 5, means current month and previous 6 months

                $startDate = $date->copy()->startOfMonth(); // Start of the month
                $endDate = $date->copy()->endOfMonth(); // End of the month

                // Sum total revenue for this month
                $monthlyRevenue = Order::whereBetween('created_at', [$startDate, $endDate])
                    ->sum('total_price');

                // Add to the revenue array with readable month label
                $revenue[] = [
                    'revenue' => $monthlyRevenue,
                    'month' => $date->format('F Y'), // e.g. "January 2023"
                ];
            }

            // Reverse the array so it shows from oldest to newest (current month first, on the right)
            $revenue = array_reverse($revenue);

            // Get last month revenue
            $previousMonthRevenue = Order::whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year)
                ->sum('total_price');

            // Get this month revenue
            $currentMonthRevenue = Order::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total_price');


            // Calculate the percentage change from last month to this month
            if ($previousMonthRevenue > 0) {
                // Revenue change percentage formula
                $revenueChangePercentage = (($currentMonthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100;
            } else {
                $revenueChangePercentage = ($currentMonthRevenue > 0) ? 100 : 0; // If last month was zero and this month is not, it's a 100% increase
            }


            // Format the revenue change percentage
            if ($revenueChangePercentage > 0) {
                $formattedRevenueChangePercentage = "↑ " . abs($revenueChangePercentage) . " from last month";
            } elseif ($revenueChangePercentage < 0) {
                $formattedRevenueChangePercentage = "↓ " . abs($revenueChangePercentage) . " from last month";
            } else {
                $formattedRevenueChangePercentage = "0%";
            }

            return response()->json([
                'revenue' => $revenue,
                'revenue_change' => $formattedRevenueChangePercentage,
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
