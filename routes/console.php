<?php

use Illuminate\Support\Facades\Schedule;

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');


// Requires `php artisan schedule:run` every minute on the VPS.
Schedule::command('marketplace:expire-reservations')->everyMinute()->withoutOverlapping();
Schedule::command('marketplace:expire-hml-sandbox')->everyMinute()->withoutOverlapping();
