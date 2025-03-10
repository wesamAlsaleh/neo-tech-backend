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

        // Get all products where onSale is true and sale_end has passed
        $products = Product::where('onSale', true)
            ->where('sale_end', '<', $now) // Add this condition to check if sale_end has passed
            ->get();

        // Update onSale attribute to false for each product where sale_end has passed
        foreach ($products as $product) {
            $product->update([
                'onSale' => false,
                'discount' => 0,
                'sale_start' => null,
                'sale_end' => null,
            ]);
        }

        // Output success message
        $this->info('Sale end dates checked and updated successfully.');

        // Return 0 to indicate success
        return 0;
    }
}


// To test the command, run the following command in the terminal:
// php artisan app:check-sale-end-date
// This command will check if the sale end date has passed for any products and update the onSale attribute to false if necessary.

// To list all available commands, run the following command:
// php artisan list
