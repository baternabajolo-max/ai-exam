<?php

use Illuminate\Foundation\DevCommands;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

DevCommands::artisan(
    'queue:work database_math --queue=math-pdf --tries=1 --timeout=7200',
    'math-pdf',
);

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('reports:faculty-question-bank-daily-summary')
    ->dailyAt('00:00')
    ->timezone('Asia/Manila')
    ->withoutOverlapping();
