<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

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

    // Log to export all products with statistics to a downloadable CSV file
    public function getAllProductsInCSV()
    {
        try {
            // Get all products from the database, ordered by the number of views (most viewed first)
            $products = Product::orderBy('product_view', 'desc')
                ->get();

            // Define the name of the CSV file eg: neoTech_products_data_2023-10-01.csv
            $filename = 'neoTech_products_data_' . date('Y-m-d') . '.csv';

            // Set the HTTP headers to tell the browser this is a file download of type CSV
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            // Define a callback that generates the CSV content
            $callback = function () use ($products) {
                // Open a write-only stream to the output buffer
                $file = fopen('php://output', 'w'); // this will write to the output buffer

                // Write the column headers to the first row of the CSV
                fputcsv($file, [
                    'Product Name',
                    'Rating',
                    'Units Sold',
                    'Views',
                    'Active Status',
                    'Created At',
                    'Updated At'
                ]); // Column headers

                // Loop through each product and write its data as a row in the CSV
                foreach ($products as $product) {
                    fputcsv($file, [
                        $product->product_name,
                        $product->product_rating,
                        $product->product_sold,
                        $product->product_view,
                        $product->is_active ? 'Active' : 'Inactive', // Convert boolean to text
                        $product->created_at->format('Y-m-d H:i:s'),
                        $product->updated_at->format('Y-m-d H:i:s')
                    ]); // Product data
                }

                // Close the output stream
                fclose($file); // this will flush the output buffer and send the CSV to the browser
            };

            // Return a streamed response that runs the callback and sends the CSV to the browser
            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to export products',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to get the sales report
    public function getSalesReport(Request $request)
    {
        try {
            // Validate the request parameters
            $request->validate([
                'page' => 'integer|min:1',
                'per_page' => 'integer|min:1|max:100',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            // Get pagination params from the request
            $page = $request->input('page', 1); // default to page 1
            $perPage = $request->input('per_page', 10); // default to 10 items per page

            // Get the start and end dates from the request
            $startDate = Carbon::parse($request->input('start_date'));
            $endDate = Carbon::parse($request->input('end_date'));

            // Get the sales report data
            $orders = Order::whereBetween('created_at', [$startDate, $endDate])
                ->select([
                    'id',
                    'total_price',
                    'created_at',
                ])
                ->with('orderItems')
                ->get();

            // Initialize an array to hold the products sold with their statistics
            /**
             * productsSold = [
             * {
             *      "product_id": number,
             *    "product_name": string,
             *   "product_unit_price": number,
             *     "quantity_sold": number
             *   "total_revenue": number
             * }, // index 0
             * ]; // array of objects
             */
            $productSalesData = [];

            // Loop through the sales and get the products sold and the total amount for each product
            foreach ($orders as $order) {
                // Get the order items for each sale report
                $orderItems = $order->orderItems;

                // Loop through the order items in each sale report
                foreach ($orderItems as $orderItem) {
                    // Get the product ID and quantity sold
                    $productId = $orderItem->product_id;
                    $quantity = $orderItem->quantity;
                    $lineTotal = $orderItem->price; // Total price for the line item (unit price * quantity)

                    // Get the product from the database
                    $product = Product::find($productId);

                    // if the product is not found, skip to the next iteration
                    if (!$product) continue;

                    // Get the product name
                    $productName = $product->product_name;

                    // Get the Current unit price based on whether the product is on sale
                    $unitPrice = $product->onSale ? $product->product_price_after_discount : $product->product_price;

                    // Get all the product id's from the productSalesData array
                    $productIds = array_column($productSalesData, 'product_id');

                    // Search for existing product entry (returns the index of the product in the array or false if not found)
                    $index = array_search($productId, $productIds); // Check if the product already exists in the array

                    // If the product already exists in the array, update the quantity and total price
                    if ($index !== false) {
                        $productSalesData[$index]['quantity_sold'] += $quantity;
                        $productSalesData[$index]['total_revenue'] += $lineTotal;
                    } else {
                        $productSalesData[] = [
                            'product_id' => $productId,
                            'product_name' => $productName,
                            'product_unit_price' => $unitPrice, // Current unit price of the product
                            'quantity_sold' => $quantity,
                            'total_revenue' => $lineTotal, // Total revenue for the product (unit price at checkout * quantity sold)
                        ];
                    }
                }
            }

            // Get the total number of units sold
            $totalUnitsSold = array_sum(array_column($productSalesData, 'quantity_sold'));

            // Convert the array to a Laravel collection so we can use collection methods (e.g., paginate)
            $collection = collect($productSalesData);

            // Create paginator instance for the collection
            $paginatedCollection = new LengthAwarePaginator(
                $collection->forPage($page, $perPage)->values(), // Items for the current page
                $collection->count(), // Total number of items
                $perPage, // Items per page
                $page, // Current page
                ['path' => $request->url(), 'query' => $request->query()] // Preserve query parameters in pagination links
            );

            return response()->json([
                'message' => 'Sales report fetched successfully',
                'report' => $paginatedCollection,
                'total_units_sold' => $totalUnitsSold,
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
                'message' => 'An error occurred while fetching the sales report.',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to get the sales report in Excel format

}
