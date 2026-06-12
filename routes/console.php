<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('app:generate-weekly-report --send-wa')->weeklyOn(7, '01:00');
Schedule::command('app:delete-expire-qrcodes')->daily();
Schedule::command('app:delete-penalty-proof')->weekly();