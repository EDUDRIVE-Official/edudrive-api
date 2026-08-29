<?php

declare(strict_types=1);

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('identity:purge-inactive-accounts')->daily();
Schedule::command('sanctum:prune-expired --hours=24')->daily();
Schedule::command('async-processing:cleanup')->daily();
Schedule::command('backup:database')->daily();
