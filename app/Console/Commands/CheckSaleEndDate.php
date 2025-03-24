<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class CheckSaleEndDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:check-sale-end-date';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if the sale end date has passed and update onSale attribute to false';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the current date and time
        $now = now();

        // Log the current time for debugging
        // Log::info('Running sale end date check at: ' . $now);

        // Get all products where onSale is true and sale_end has passed
        $products = Product::where('onSale', true)
            ->where('sale_end', '<', $now)
            ->get();

        // Log the number of products found for debugging
        // Log::info('Number of products to update: ' . $products->count());

        // Update onSale attribute to false for each product where sale_end has passed
        foreach ($products as $product) {
            // Log::info('Updating product ID: ' . $product->id . ' - Sale end: ' . $product->sale_end);

            $product->update([
                'onSale' => false,
                'discount' => 0,
                'sale_start' => null,
                'sale_end' => null,
                'product_price_after_discount' => 0,
            ]);

            // Log::info('Product ID: ' . $product->id . ' updated successfully.');
        }

        // Log completion message
        // Log::info('Sale end date check completed.');
    }
}


// To test the command, run the following command in the terminal:
// php artisan app:check-sale-end-date
// This command will check if the sale end date has passed for any products and update the onSale attribute to false if necessary.

// To list all available commands, run the following command:
// php artisan list
