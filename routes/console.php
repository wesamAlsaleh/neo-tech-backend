<?php

use App\Models\Product;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

use Illuminate\Support\Facades\Log;

// `php artisan schedule:list`to list all scheduled commands
// `php -r "echo date('Y-m-d H:i:s');"` to get the current date and time in the terminal
// `php artisan schedule:work` to run the scheduled commands, (todo: run this in the server terminal in production! without it the scheduled commands won't run)


// Schedule a command to run every minute
Schedule::call(function () {
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
        ]);

        // Log::info('Product ID: ' . $product->id . ' updated successfully.');
    }

    // Log completion message
    // Log::info('Sale end date check completed.');
})->everyMinute(); // Run this function every minute
