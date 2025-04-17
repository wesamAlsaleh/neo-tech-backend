<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    // Logic to remove order item from an order
    public function removeOrderItem(Request $request)
    {
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

            // Fetch updated order items
            $updatedItems = OrderItem::where('order_id', $order->id)->get();

            // Recalculate total
            $total = 0;
            foreach ($updatedItems as $item) {
                $total += $item->price * $item->quantity;
            }

            return response()->json([
                'message' => 'Order item removed successfully',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Handle order not found
            return response()->json([
                'message' => 'Order not found',
                'devMessage' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            // Handle exception
            return response()->json([
                'message' => 'Failed to remove order item',
                'devMessage' => $e->getMessage(),
            ], 500);
        }
    }
}
