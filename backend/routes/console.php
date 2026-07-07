<?php

use App\Jobs\IngestTrendingJob;
use App\Jobs\RelatedRisingIngestJob;
use App\Jobs\ScoreTopicsJob;
use App\Models\Setting;
use App\Services\DashboardService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Dynamic ingest — runs every minute, dispatches based on configured interval
Schedule::call(function () {
    try {
        $enabled  = Setting::getBool('auto_parse_enabled', true);
        $interval = Setting::getInt('parse_interval_minutes', 30);
        $lastRun  = Setting::get('ingest_last_dispatched_at');

        if (!$enabled) {
            return;
        }

        if ($lastRun && now()->diffInMinutes($lastRun) < $interval) {
            return;
        }

        foreach (DashboardService::ingestGeos() as $geo) {
            IngestTrendingJob::dispatch($geo)->onQueue('default');
        }

        Setting::set('ingest_last_dispatched_at', now()->toIso8601String());
    } catch (\Throwable) {
        // Settings table may not exist yet during migrations
    }
})->everyMinute()->name('ingest-trending-dynamic');

// Score topics every hour — batch of 50 per run
Schedule::job(new ScoreTopicsJob(50))->hourly()->name('score-topics');

// Ingest related-rising queries for top scored topics — every 6 hours
Schedule::call(function () {
    $topTopics = \Illuminate\Support\Facades\DB::table('topics')
        ->whereNotNull('last_scored_at')
        ->whereIn('status', ['exploding', 'regular'])
        ->orderBy('score', 'desc')
        ->limit(30)
        ->get(['keyword', 'geo']);

    foreach ($topTopics as $topic) {
        RelatedRisingIngestJob::dispatch($topic->keyword, $topic->geo)->onQueue('default');
    }
})->everySixHours()->name('ingest-related-rising');
