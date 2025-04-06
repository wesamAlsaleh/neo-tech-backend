<?php

namespace App\Console\Commands;

use App\Models\CartItem;
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
            } else {
                $this->error("Failed to update cart item ID: {$cartItem->id}");
            }

            $this->info('Cart prices updated successfully.');
        }
    }
}
