<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DashboardController extends Controller
{
    // Logic to get total user per specific time (daily, weekly, monthly)
    public function getTotalUser(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'time_period' => 'required|in:daily,weekly,monthly',
            ]);

            // Get the time period from the request
            $timePeriod = $request->input('time_period');

            // Fetch the total users based on the time period
            switch ($timePeriod) {
                case 'daily':
                    $totalUsers = DB::table('users')
                        ->whereDate('created_at', now()->format('Y-m-d'))
                        ->count();
                    break;

                case 'weekly':
                    $totalUsers = DB::table('users')
                        ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
                        ->count();
                    break;

                case 'monthly':
                    $totalUsers = DB::table('users')
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->count();
                    break;

                default:
                    return response()->json([
                        'message' => 'The time period must be daily, weekly, or monthly.',
                        'devMessage' => 'INVALID_TIME_PERIOD',
                    ], 400);
            }

            return response()->json([
                'total_users' => $totalUsers,
            ], 200);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching the total users.',
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
            // Fetch the system performance logs
            $performanceLogs = DB::table('system_performance_logs')
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

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
}
