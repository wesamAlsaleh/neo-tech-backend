<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderItemController extends Controller
{
    // Logic to remove order item from an order
    public function removeOrderItem(Request $request)
    {
        // Start a database transaction (to ensure data integrity)
        DB::beginTransaction();

        try {
            // Validate the request
            $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
                'item_id' => 'required|integer|exists:order_items,id',
            ]);

            // Get the order ID and item ID from the request
            $orderId = $request->input('order_id');
            $itemId = $request->input('item_id');

            // Get the order by ID
            $order = Order::findOrFail($orderId);

            // Get the item to remove
            $orderItem = OrderItem::where('id', $itemId)
                ->where('order_id', $order->id)
                ->first();

            // Check if the order item exists in the order
            if (!$orderItem) {
                return response()->json([
                    'message' => 'Order item not found in the order',
                    'devMessage' => "ORDER_ITEM_NOT_FOUND"
                ], 404);
            }

            // Count all items in this order
            $itemCount = OrderItem::where('order_id', $order->id)->count();

            // If there is only one item left, it cannot be removed
            if ($itemCount <= 1) {
                return response()->json([
                    'message' => 'Cannot remove the last order item',
                    'devMessage' => "CANNOT_REMOVE_LAST_ITEM"
                ], 404);
            }

            // Delete the item
            $orderItem->delete();

            // Fetch updated order items after deletion
            $updatedItems = OrderItem::where('order_id', $order->id)->get();

            // Recalculate total
            $total = 0;
            foreach ($updatedItems as $item) {
                $total += $item->price;
            }

            // Update the order total
            $order->total_price = $total;
            $order->save();

            // Commit the transaction after all operations are successful
            DB::commit();

            return response()->json([
                'message' => 'Order item removed successfully',
            ], 200);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            // Handle order not found
            return response()->json([
                'message' => 'Order not found',
                'devMessage' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            // If an error occurs, rollback the transaction
            DB::rollBack();

            // Handle exception
            return response()->json([
                'message' => 'Failed to remove order item',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to add order item to an order
    public function addOrderItem(Request $request)
    {
        // Start a database transaction (to ensure data integrity)
        DB::beginTransaction();

        try {
            // Validate the request
            $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
                'product_id' => 'required|integer|exists:products,id',
                'quantity' => 'required|integer|min:1',
            ]);

            // Get the order ID and item ID from the request
            $orderId = $request->input('order_id');
            $productId = $request->input('product_id');
            $quantity = $request->input('quantity');

            // Get the order by ID
            $order = Order::findOrFail($orderId);

            // Get the product by ID
            $product = Product::findOrFail($productId);

            // Get product unit price based on whether it's a discount or not
            $unitPrice = $product->onSale ? $product->product_price_after_discount : $product->product_price;

            // Check if the product is available
            if (!$product) {
                return response()->json([
                    'message' => 'Product not found',
                    'devMessage' => "PRODUCT_NOT_FOUND"
                ], 404);
            }

            // If product is not inactive, prevent adding to order
            if (!$product->is_active) {
                return response()->json([
                    'message' => "{$product->product_name} is not available",
                    'devMessage' => "PRODUCT_NOT_AVAILABLE"
                ], 422);
            }

            // Get order items
            $orderItems = OrderItem::where('order_id', $order->id)->get();

            // Check if the product is already in the order
            foreach ($orderItems as $item) {
                if ($item->product_id == $productId) {
                    return response()->json([
                        'message' => "{$product->product_name} is already in the order",
                        'devMessage' => "PRODUCT_ALREADY_IN_ORDER"
                    ], 422);
                }
            }

            // This prevents overselling when inventory is low
            $MINIMUM_STOCK_THRESHOLD = 5;

            // If product is stock is low, prevent adding to order
            if ($product->product_stock < $quantity + $MINIMUM_STOCK_THRESHOLD) {
                return response()->json([
                    'message' => "{$product->product_name} stock is low",
                    'devMessage' => "PRODUCT_STOCK_LOW"
                ], 422);
            }

            // Check if the order is already completed
            if ($order->status == 'completed') {
                return response()->json([
                    'message' => 'Cannot add items to a completed order',
                    'devMessage' => "ORDER_COMPLETED"
                ], 422);
            }

            // Check if the order is already cancelled
            if ($order->status == 'cancelled') {
                return response()->json([
                    'message' => 'Cannot add items to a cancelled order',
                    'devMessage' => "ORDER_CANCELLED"
                ], 422);
            }

            // Create a new order item
            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => $quantity,
                'price' => $unitPrice * $quantity,
            ]);

            // Update the product stock
            $product->product_stock -= $quantity;
            $product->product_sold += $quantity;
            $product->save();

            // Fetch updated order items after deletion
            $updatedOrderItems = OrderItem::where('order_id', $order->id)->get();

            // Recalculate total
            $total = 0;
            foreach ($updatedOrderItems as $item) {
                $total += $item->price * $item->quantity;
            }

            // Update the order total
            $order->total_price = $total;
            $order->save();

            // Commit the transaction after all operations are successful
            DB::commit();

            return response()->json(
                [
                    'message' => "{$product->product_name} added to order #{$order->id} successfully",
                ],
                201
            );
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            // Handle order not found
            return response()->json([
                'message' => 'Order not found',
                'devMessage' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            // If an error occurs, rollback the transaction
            DB::rollBack();

            // Handle exception
            return response()->json([
                'message' => 'Failed to add order item',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }

    // Logic to update order item quantity
    public function  updateOrderItemQuantity(Request $request)
    {
        // Start a database transaction (to ensure data integrity)
        DB::beginTransaction();

        try {
            // Validate the request
            $request->validate([
                'order_id' => 'required|integer|exists:orders,id',
                'item_id' => 'required|integer|exists:order_items,id',
                'quantity' => 'required|integer|min:1',
            ]);

            // Get the order ID and item ID from the request
            $orderId = $request->input('order_id');
            $itemId = $request->input('item_id');

            // Get the order by ID
            $order = Order::findOrFail($orderId);

            // Get the item to update
            $orderItem = OrderItem::where('id', $itemId)
                ->where('order_id', $order->id)
                ->first();

            // Check if the order item exists in the order
            if (!$orderItem) {
                return response()->json([
                    'message' => 'Order item not found in the order',
                    'devMessage' => "ORDER_ITEM_NOT_FOUND"
                ], 404);
            }


            // Get the product by ID
            $product = Product::findOrFail($orderItem->product_id);

            // Check if the product is available
            if (!$product) {
                return response()->json([
                    'message' => 'Product not found',
                    'devMessage' => "PRODUCT_NOT_FOUND"
                ], 404);
            }

            // If product is inactive, prevent adding to order
            if (!$product->is_active) {
                return response()->json([
                    'message' => "{$product->product_name} is not available",
                    'devMessage' => "PRODUCT_NOT_AVAILABLE"
                ], 422);
            }

            // Get the requested quantity and current quantity
            $requestedQty = $request->input('quantity');
            $currentQty = $orderItem->quantity;
            $availableStock = $product->product_stock;

            // Check if the inventory is low
            $MINIMUM_STOCK_THRESHOLD = 5;

            // If the stock is low
            if ($availableStock < $currentQty + $MINIMUM_STOCK_THRESHOLD) {
                // Return an error if the requested quantity is greater than the available stock, but if the requested quantity is less than the current quantity, allow it
                if (!($requestedQty < $currentQty)) {
                    return response()->json([
                        'message' => "{$product->product_name} stock is low, only {$availableStock} left",
                        'devMessage' => "PRODUCT_STOCK_LOW"
                    ], 422);
                }
            }

            // Check if the order is already cancelled
            if ($order->status == 'cancelled') {
                return response()->json([
                    'message' => 'Cannot update items in a cancelled order',
                    'devMessage' => "ORDER_CANCELLED"
                ], 422);
            }

            // Get the unit price based on whether it's a discount or not
            $unitPrice = $product->onSale ? $product->product_price_after_discount : $product->product_price;
            $newQuantity = $request->input('quantity');

            // If the new quantity is same as the old quantity, no need to update
            if ($newQuantity == $orderItem->quantity) {
                return response()->json([
                    'message' => "{$product->product_name} quantity is already {$newQuantity} in order #{$order->id}",
                ], 200);
            }

            // Update the item quantity
            $orderItem->quantity = $newQuantity;
            $orderItem->price = $unitPrice * $newQuantity; // Update the price based on new quantity
            $orderItem->save();

            // Update the product stock
            $product->product_stock -= $newQuantity;
            $product->product_sold += $newQuantity;
            $product->save();

            // Fetch updated order items after deletion
            $updatedItems = OrderItem::where('order_id', $order->id)->get();

            // Recalculate total
            $total = 0;
            foreach ($updatedItems as $item) {
                $total += $item->price;
            }

            // Update the order total
            $order->total_price = $total;
            $order->save();

            // Commit the transaction after all operations are successful
            DB::commit();

            return response()->json([
                'message' => "{$product->product_name} quantity updated successfully, therefore the total price is now {$order->total_price}",
            ], 200);
        } catch (ValidationException $e) {
            // Get the first error message from the validation errors
            $errorMessages = collect($e->errors())->flatten()->first();

            return response()->json([
                'message' => $errorMessages,
                'devMessage' => $e->errors(),
            ], 422);
        } catch (ModelNotFoundException $e) {
            // Handle order not found
            return response()->json([
                'message' => 'Order not found',
                'devMessage' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            // If an error occurs, rollback the transaction
            DB::rollBack();

            // Handle exception
            return response()->json([
                'message' => 'Failed to remove order item',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }
}
