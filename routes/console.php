<?php

use Illuminate\Support\Facades\Schedule;


// `php artisan schedule:list`to list all scheduled commands
// `php artisan schedule:run` to run the scheduled commands
// `php -r "echo date('Y-m-d H:i:s');"` to get the current date and time in the terminal
// `php artisan schedule:work` to run the scheduled commands, (todo: run this in the server terminal in production! without it the scheduled commands won't run)
// ->time on schedule page on the documentation


// Call the command to check the sale end date of a product (activate/deactivate)
Schedule::command('app:check-sale-end-date')->everyMinute();

// Call the command to manage the flash sale (activate/deactivate)
Schedule::command('app:activate-flash-sale')->hourly();

// Call the command to manage the cart products price (with discount/without discount)
Schedule::command('app:update-cart-prices')->everyFifteenMinutes();
