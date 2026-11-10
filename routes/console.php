<?php

use App\Services\GapDetector;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('ews:detect', function (GapDetector $detector) {
    $this->info('Analysed '.$detector->runAll().' students.');
})->purpose('Run competency gap detection for every student');

Schedule::command('ews:detect')->dailyAt('18:00');
