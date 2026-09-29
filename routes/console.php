<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('dorm:backup-daily', function () {
    $backupStatus = $this->call('dorm:backup');

    if ($backupStatus !== 0) {
        return $backupStatus;
    }

    return $this->call('dorm:backup-prune');
})->purpose('Create a backup and retain the latest verified sets');

\Illuminate\Support\Facades\Schedule::command('dorm:backup-daily')
    ->dailyAt('02:00')
    ->timezone('Asia/Bangkok')
    ->withoutOverlapping(120)
    ->appendOutputTo(storage_path('logs/dorm-backup.log'));
