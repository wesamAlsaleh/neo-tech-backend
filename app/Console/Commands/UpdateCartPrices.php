<?php

namespace App\Console\Commands;

use App\Models\CartItem;
use App\Models\SystemPerformanceLog;
use Illuminate\Console\Command;

class UpdateCartPrices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:update-cart-prices';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update cart prices based on active sale discounts';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Fetch all cart items with their related products
        $cartItems = CartItem::with('product')->get();

        // Loop through each cart item
        foreach ($cartItems as $cartItem) {
            // Get the product associated with the cart item
            $product = $cartItem->product;

            // Get the quantity of the cart item
            $quantity = $cartItem->quantity;

            // If the product is not found, log an error and continue
            if (!$product) {
                $this->error("Product not found for cart item ID: {$cartItem->id}");
                continue;
            }

            // Set the new price based on the product's sale price
            $newPrice = $product->onSale ? $product->product_price_after_discount : $product->product_price;

            // Update the cart item price
            $cartItem->price = $newPrice * $quantity;

            // Save the updated cart item
            if ($cartItem->save()) {
                $this->info("Updated cart item ID: {$cartItem->id} with new price: {$cartItem->price}");
                $this->info('Cart prices updated successfully.');
            } else {
                $this->error("Failed to update cart item ID: {$cartItem->id}");
                $this->error('Error saving the cart item.');

                // Add performance log
                SystemPerformanceLog::create([
                    'log_type' => 'error',
                    'message' => "Failed to update cart item ID: {$cartItem->id}",
                    'context' => 'Checking cart prices for active sale discounts ended with error.',
                    'user_id' => null,
                    'status_code' => 500,
                ]);
            }
        }

        // Add performance log
        SystemPerformanceLog::create([
            'log_type' => 'info',
            'message' => "Cart prices updated successfully.",
            'context' => 'Checking cart prices for active sale discounts ended successfully.',
            'user_id' => null,
            'status_code' => 201,
        ]);
    }
}
