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
        // Get all sales where the end date has passed and onSale is still true
        $products = Product::where('sale_end', '<', now())
            ->where('onSale', true)
            ->get();

        // Update onSale attribute to false for each sale
        foreach ($products as $product) {
            // Update the product after the sale end date has passed
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
