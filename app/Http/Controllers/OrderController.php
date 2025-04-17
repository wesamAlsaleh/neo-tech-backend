<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    // logic to display all orders with pagination [for admin dashboard]
    public function index(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'perPage' => 'nullable|integer|min:1|max:15', // Number of products per page
                'page' => 'nullable|integer|min:1', // Number of the current page
            ]);

            // Eager load user with selected fields using user() relationship
            $orders = Order::with(['user:id,first_name,last_name,email'])
                ->orderBy('created_at', 'desc') // Order by created_at in descending order
                ->paginate(
                    $validated['perPage'] ?? 10, // Default to 10 per page if not provided
                    ['*'], // Get all columns
                    'flashSaleProducts', // Custom pagination page name
                    $validated['page'] ?? 1 // Default to page 1 if not provided
                );

            return response()->json([
                'message' => 'Orders fetched successfully',
                'orders' => $orders,
                'total_orders' => Order::count(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error occurred while fetching orders',
                'devMessage' => $e->getMessage()
            ]);
        }
    }

    // Logic to set order status to pending by order ID [for admin dashboard]
    public function setOrderStatusToPending(int $id)
    {
        try {
            // Find the order by ID
            $order = Order::findOrFail($id);

            // Check if the order is already pending
            if ($order->status === 'pending') {
                return response()->json([
                    'message' => "Order with ID {$id} is already pending",
                    'devMessage' => 'ORDER_ALREADY_PENDING'
                ]);
            }

            // Update the order status to pending
            $order->status = 'pending';
            $order->save();

            return response()->json([
                'message' => "Order with ID {$id} status updated to pending",
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Order not found',
                'devMessage' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error occurred while updating order status',
                'devMessage' => $e->getMessage()
            ]);
        }
    }

    // Logic to set order status to completed by order ID [for admin dashboard]
    public function setOrderStatusToCompleted(int $id)
    {
        try {
            // Find the order by ID
            $order = Order::findOrFail($id);

            // Check if the order is already completed
            if ($order->status === 'completed') {
                return response()->json([
                    'message' => "Order with ID {$id} is already completed",
                    'devMessage' => 'ORDER_ALREADY_COMPLETED'
                ]);
            }

            // Update the order status to completed
            $order->status = 'completed';
            $order->save();

            return response()->json([
                'message' => "Order with ID {$id} status updated to completed",
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Order not found',
                'devMessage' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error occurred while updating order status',
                'devMessage' => $e->getMessage()
            ]);
        }
    }

    // Logic to set order status to canceled by order ID [for admin dashboard]
    public function setOrderStatusToCanceled(int $id)
    {
        try {
            // Find the order by ID
            $order = Order::findOrFail($id);

            // Check if the order is already canceled
            if ($order->status === 'canceled') {
                return response()->json([
                    'message' => "Order with ID {$id} is already canceled",
                    'devMessage' => 'ORDER_ALREADY_CANCELED'
                ]);
            }

            // Update the order status to canceled
            $order->status = 'canceled';
            $order->save();

            return response()->json([
                'message' => "Order with ID {$id} status updated to canceled",
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Order not found',
                'devMessage' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error occurred while updating order status',
                'devMessage' => $e->getMessage()
            ]);
        }
    }

    // Logic to get orders based on their status with pagination [for admin dashboard (filtering)]
    public function getOrdersByStatus(String $status)
    {
        try {
            // If the status is not provided, return an error
            if (is_null($status)) {
                return response()->json([
                    'message' => 'Status not provided',
                    'devMessage' => 'STATUS_NOT_PROVIDED'
                ], 422);
            }

            // Validate the provided status
            if (!in_array($status, ['pending', 'completed', 'canceled'])) {
                return response()->json([
                    'message' => 'Invalid status provided',
                    'devMessage' => 'INVALID_STATUS'
                ], 422);
            }

            // Get the orders by status
            $orders = Order::where('status', $status)
                ->paginate(10);

            return response()->json([
                'message' => 'Orders fetched successfully',
                'orders' => $orders,
                'total_orders' => Order::where('status', $status)->count(),
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
                'message' => 'Error occurred while fetching orders',
                'devMessage' => $e->getMessage()
            ]);
        }
    }

    // Logic to get order details by order ID [for admin dashboard]
    public function show(int $id)
    {
        try {
            // Find the order by ID
            $order = Order::with('orderItems')
                ->findOrFail($id);

            // Bring the order items with details
            $order->orderItems->each(function ($item) {
                // Add product details to the order item
                $item->product = $item->product()->first();
            });

            // Get the user details
            $user = User::findOrFail($order->user_id);

            // Add user details to the order object
            $order->user = $user;

            return response()->json([
                'message' => 'Order details fetched successfully',
                'order' => $order,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Order not found',
                'devMessage' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error occurred while fetching order details',
                'devMessage' => $e->getMessage()
            ]);
        }
    }

    // Logic to edit order details by order ID [for admin dashboard]
    public function updateOrderDetails(int $id, Request $request)
    {
        // Start a database transaction (to ensure data integrity)
        DB::beginTransaction();

        try {
            // Find the order by ID
            $order = Order::findOrFail($id);

            // Sanitize empty strings into null before validation
            $request->merge(array_map(function ($value) {
                return $value === '' ? null : $value;
            }, $request->all()));

            // Validate the request
            $request->validate([
                'status' => 'nullable|string|in:pending,completed,canceled',
                'payment_method' => 'nullable|string|in:cash,credit_card,paypal,debit_card',
                'home_number' => 'nullable|string|max:20|regex:/^[A-Za-z0-9\s\-\.]+$/', // Should contain only letters, numbers, spaces, hyphens, and dots
                'street_number' => 'nullable|string|max:10',
                'block_number' => 'nullable|string|max:10|regex:/^[A-Za-z]?\d+[A-Za-z]?$/', // Called "Block No." (usually 3 digits)
                'city' => 'nullable|string|max:255',
                'items' => 'nullable|array', // (array) of old items and new items to be added
                'items.*.product_id' => [
                    'required',      // Required field - every order item must reference a product
                    'integer',       // Must be numeric integer
                    'exists:products,id'  // Verify the product exists in products table (id column)
                ],
                'items.*.quantity' => [
                    'required',      // Required field - must specify how many items ordered
                    'integer',       // Must be whole number
                    'min:1'         // Minimum quantity of 1
                ],
            ]);

            // User address fallback
            $userAddress = User::find($order->user_id)
                ->address()
                ->first();

            // If user address is provided, use it; otherwise, use the existing address
            $shippingAddress = "Building number: " . ($request->home_number ?? $userAddress->home_number)
                . ", Street number: " . ($request->street_number ?? $userAddress->street_number)
                . ", Block number: " . ($request->block_number ?? $userAddress->block_number)
                . ", City: " . ($request->city ?? $userAddress->city);

            // Update order core fields or keep the existing values
            $order->status = $request->status ?? $order->status;
            $order->payment_method = $request->payment_method ?? $order->payment_method;
            $order->shipping_address = $shippingAddress;

            // Get the order items
            $orderItems = OrderItem::where('order_id', $order->id)
                ->get();

            // If no items returned, return an error
            if ($orderItems->isEmpty()) {
                return response()->json([
                    'message' => 'No items found in the order',
                    'devMessage' => 'NO_ITEMS_FOUND'
                ], 404);
            }

            // Array to hold the product IDs (to check later if they exist in the order items or removed)
            $productIds = [];

            // Initialize the processed product count
            $processedProductCount = 0;

            // Array to hold low stock products
            $lowStockProducts = [];

            // If item are provided
            if ($request->has('items')) {
                // Loop through the items and update or overright the existing items or add new items
                foreach ($request->items as $item) {
                    // Get the product details
                    $product = Product::find($item['product_id']);

                    // If not found return an error
                    if (!$product) {
                        return response()->json([
                            'message' => "Product with ID {$item['product_id']} not found",
                            'devMessage' => 'PRODUCT_NOT_FOUND'
                        ], 404);
                    }

                    // Get the price based on whether the product is on sale or not
                    $productPrice = $product->onSale ? $product->product_price_after_discount : $product->product_price;

                    // This prevents overselling when inventory is low
                    $MINIMUM_STOCK_THRESHOLD = 1;

                    // Check if product stock is insufficient to fulfill the order
                    if ($product->product_stock < $MINIMUM_STOCK_THRESHOLD || $product->product_stock < $item['quantity']) {
                        // Add to low stock list
                        $lowStockProducts[] = [
                            'product_id' => $product->id,
                            'product_name' => $product->product_name,
                            'stock' => $product->product_stock,
                        ];
                        continue;
                    }

                    // Add the product ID to the array
                    $productIds[] = $product->id;

                    // Ensure the product is in the order items
                    $orderItem = $orderItems->where('product_id', $product->id)->first();

                    // If the product is in the order items, update the quantity, otherwise create a new order item
                    if ($orderItem) {
                        // Update existing item
                        $orderItem->quantity = $item['quantity'] ?? $orderItem->quantity;
                        $orderItem->price = $productPrice * $orderItem->quantity;
                        $orderItem->save();
                    } else {
                        // Create a new order item record
                        OrderItem::create([
                            'order_id' => $order->id,
                            'product_id' => $product->id,
                            'quantity' => $item['quantity'],
                            'price' =>  $productPrice * $item['quantity'],
                        ]);
                    }

                    // Reduce stock and increase sold count
                    $product->product_stock -= $item['quantity'];
                    $product->product_sold += $item['quantity'];
                    $product->save();

                    $processedProductCount++;
                }
            }

            // If no products were processed, return warning
            if ($processedProductCount === 0) {
                DB::rollBack();

                return response()->json([
                    'message' => count($lowStockProducts) . " items were skipped due to low stock.",
                    'devMessage' => 'ALL_ITEMS_SKIPPED_LOW_STOCK'
                ], 400); // Or 422
            }

            // Remove items not in the new list
            foreach ($orderItems as $orderItem) {
                // If the product ID is not in the new list, delete the order item
                if (!in_array($orderItem->product_id, $productIds)) {
                    $orderItem->delete();
                }
            }

            // Update the order total price by summing the prices of all order items
            $order->total_price = $order->orderItems->sum('price');

            // Save the order after all operations
            $order->save();

            // Commit the transaction after all operations are successful
            DB::commit();

            return response()->json([
                'message' => "Order #{$id} updated successfully.",
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Order not found',
                'devMessage' => $e->getMessage()
            ], 404);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // If an error occurs, rollback the transaction
            DB::rollBack();

            Log::warning('Order update skipped low-stock products', [
                'order_id' => $order->id,
                'skipped_items' => $lowStockProducts
            ]);

            return response()->json([
                'message' => 'Error occurred while updating order details',
                'devMessage' => $e->getMessage()
            ], 500);
        }
    }

    // Logic to get orders by user ID [for admin dashboard] (without pagination)
    public function getOrdersByUserId(int $userId)
    {
        try {
            // Find the user by ID
            $user = User::findOrFail($userId);

            // Get the user's
            $orders = Order::where('user_id', $user->id)
                ->with('orderItems')
                ->get();

            return response()->json([
                'message' => 'Orders fetched successfully',
                'orders' => $orders,
                'total_orders' => $user->orders()->count(),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'User not found',
                'devMessage' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error occurred while fetching orders',
                'devMessage' => $e->getMessage()
            ]);
        }
    }

    // Logic to create order from cart items [for client]
    public function checkout(Request $request)
    {
        // Start a database transaction (to ensure data integrity)
        DB::beginTransaction();

        try {
            // Get the authenticated user
            $user = Auth::user();

            // Check if the user is authenticated
            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated',
                    'devMessage' => 'USER_NOT_AUTHENTICATED'
                ], 401);
            }

            // Validate the request
            $request->validate([
                'payment_method' => 'required|string|in:cash,credit_card,paypal,debit_card',
            ]);

            // Get the user's cart items that are not checked out
            $userCart = User::find($user->id)
                ->cartItems()
                ->get();

            // Get the user address
            $userAddress = User::find($user->id)
                ->address()
                ->first();

            // Check if the cart is empty
            if ($userCart->isEmpty()) {
                return response()->json([
                    'message' => 'Your cart is empty, please add items to your cart to proceed',
                    'devMessage' => 'EMPTY_CART'
                ], 400);
            }

            // If user address is null, return an error
            if (is_null($userAddress)) {
                return response()->json([
                    'message' => 'Please add an address to your account to proceed with the order',
                    'devMessage' => 'ADDRESS_NOT_FOUND'
                ], 400);
            }

            // Calculate the total price of the cart
            $totalPrice = $userCart->sum('price');

            // Format the shipping address
            $shippingAddress = "Building number:{$userAddress->home_number}, Street number:{$userAddress->street_number}, Block number:{$userAddress->block_number} ,City:{$userAddress->city}";

            // Loop through the cart items and check if the product is in stock
            foreach ($userCart as $cartItem) {
                // Get the product from the database
                $product = Product::findOrFail($cartItem->product_id);

                // Initialize the minimum stock threshold (checkout is allowed if stock is greater than this value)
                $minimumStockThreshold = 5;

                // Check if the product is is less than the minimum stock threshold
                if ($product->product_stock < $minimumStockThreshold) {
                    return response()->json([
                        'message' => "Product {$product->product_name} is out of stock, please remove it from your cart",
                        'devMessage' => 'OUT_OF_STOCK'
                    ], 400);
                }
            }

            // Create a new order record
            $order = Order::create([
                'user_id' => $user->id,
                'total_price' => $totalPrice,
                'status' => 'pending',
                'payment_method' => $request->payment_method ?? 'cash',
                'shipping_address' => $shippingAddress,
            ]);

            // Create order items for each item in the cart
            foreach ($userCart as $cartItem) {
                // Create a new order item record
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'quantity' => $cartItem->quantity,
                    'price' => $cartItem->price,
                ]);

                // Get the product from the database
                $product = Product::findOrFail($cartItem->product_id);

                // Reduce the product stock
                $product->product_stock -= $cartItem->quantity;

                // Increase the product sold count
                $product->product_sold += $cartItem->quantity;

                // Save the product
                $product->save();

                // Remove the cart item
                $cartItem->delete();
            }

            // Commit the transaction after all operations are successful
            DB::commit();

            return response()->json([
                'message' => "Your order with ID {$order->id} has been created successfully, your order now is {$order->status} and will be shipped to {$shippingAddress}",
                'order_id' => $order->id,
                'total_price' => $order->total_price,
                'shipping_address' => $order->shipping_address,
                'payment_method' => $order->payment_method,
                'order_status' => $order->status,
            ]);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Product not found',
                'devMessage' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            // If an error occurs, rollback the transaction
            DB::rollBack();

            return response()->json([
                'message' => 'Error occurred while creating order',
                'devMessage' => $e->getMessage()
            ]);
        }
    }

    // Logic to get all the user orders without the items [for client] (with pagination)
    public function getUserOrders()
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Check if the user is authenticated
            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Get the user's orders with pagination
            $orders = Order::where('user_id', $user->id)
                ->paginate(10);

            return response()->json([
                'message' => 'Orders fetched successfully',
                'orders' => $orders,
                'total_orders' => $orders->total(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error occurred while fetching orders',
                'devMessage' => $e->getMessage()
            ]);
        }
    }

    // Logic to get order details by order ID [for client]
    public function getUserOrderDetails(int $id)
    {
        try {
            // Get the authenticated user
            $user = Auth::user();

            // Check if the user is authenticated
            if (!$user) {
                return response()->json([
                    'message' => 'User not authenticated'
                ], 401);
            }

            // Find the order by ID
            $order = Order::with('orderItems')
                ->where('user_id', $user->id)
                ->findOrFail($id);

            // Bring the order items with details
            $order->orderItems->each(function ($item) {
                // Add product details to the order item
                $item->product = $item->product()->first();
            });

            return response()->json([
                'message' => "Order for '{$user->first_name}' fetched successfully",
                'order' => $order,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Order not found',
                'devMessage' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error occurred while fetching order details',
                'devMessage' => $e->getMessage()
            ]);
        }
    }
}
