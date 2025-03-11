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

        // Deactivate expired flash sales
        FlashSale::where('end_date', '<=', $now)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Deactivate all active flash sales (only one should be active at a time)
        FlashSale::where('is_active', true)
            ->update(['is_active' => false]);

        // Activate the flash sale that should be active now
        $flashSaleToActivate = FlashSale::where('start_date', '<=', $now)
            ->where('end_date', '>', $now)
            ->orderBy('start_date', 'asc') // Prioritize the earliest starting flash sale
            ->first();

        if ($flashSaleToActivate) {
            $flashSaleToActivate->update(['is_active' => true]);

            // Log the activation of the flash sale
            Log::info('Flash sale activated: ' . $flashSaleToActivate->name);
        }

        $this->info('Flash sale activation check completed.');
    }
}
