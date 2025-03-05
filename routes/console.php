<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

// Artisan::command('inspire', function () {
//     $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

// This command will be scheduled to run every hour to check if the sale end date has passed and update the onSale attribute to false
Artisan::command('app:check-sale-end-date', function () {})
    ->purpose('Check if the sale end date has passed and update onSale attribute to false')
    ->everyMinute();
