<?php

use App\Jobs\IngestTrendingJob;
use App\Jobs\ScoreTopicsJob;
use App\Services\DashboardService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Ingest trending for all configured geos every 30 minutes
Schedule::call(function () {
    foreach (DashboardService::ingestGeos() as $geo) {
        IngestTrendingJob::dispatch($geo)->onQueue('default');
    }
})->everyThirtyMinutes()->name('ingest-trending');

// Score topics every hour — batch of 50 per run
Schedule::job(new ScoreTopicsJob(50))->hourly()->name('score-topics');
