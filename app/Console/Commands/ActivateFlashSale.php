<?php

namespace App\Console\Commands;

use App\Models\FlashSale;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ActivateFlashSale extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:activate-flash-sale';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate the flash sale if the current time is within its start and end dates, and deactivate others.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Get the current date and time
        $now = now();

        // Get all flash sales
        $flashSales = FlashSale::all();

        // Loop through all flash sales and activate the one that is within its start and end dates
        foreach ($flashSales as $flashSale) {
            // Check if the current time is within the start and end dates of the flash sale
            if ($now->between($flashSale->start_date, $flashSale->end_date)) {
                // Active the flash sale that its time
                $flashSale->update(['is_active' => true]);

                // Log that the flash sale has been activated
                Log::info("Flash sale with ID {$flashSale->name} has been activated.");

                // No need to check other flash sales
                break;
            } else {
                // If not within the start and end dates, deactivate the flash sale
                $flashSale->update(['is_active' => false]);

                // Log that the flash sale has been deactivated
                Log::info("Flash sale with ID {$flashSale->id} has been deactivated.");
            }
        }
    }
}
