<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('simpledesk:agent-statuses:expire')
    ->everyMinute()
    ->withoutOverlapping(5)
    ->name('agent-statuses:expire');
