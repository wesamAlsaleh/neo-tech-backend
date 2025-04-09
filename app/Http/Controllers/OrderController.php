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

    // Logic to get orders based on their status with pagination [for admin dashboard]
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
