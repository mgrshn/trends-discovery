<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use BackedEnum;

class JobsMonitor extends Page
{
    protected string $view = 'filament.pages.jobs-monitor';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?string $navigationLabel = 'Jobs Monitor';

    protected static ?string $title = 'Jobs Monitor';

    public function getStats(): array
    {
        $pending = DB::table('jobs')->count();
        $failed  = DB::table('failed_jobs')->count();

        $processedToday = DB::table('topic_metrics_history')
            ->whereDate('computed_at', today())
            ->count();

        $lastIngest = DB::table('topics')
            ->max('updated_at');

        return [
            'pending'          => $pending,
            'failed'           => $failed,
            'scored_today'     => $processedToday,
            'last_ingest'      => $lastIngest ? \Carbon\Carbon::parse($lastIngest)->diffForHumans() : 'never',
            'topics_total'     => DB::table('topics')->count(),
            'topics_scored'    => DB::table('topics')->whereNotNull('last_scored_at')->count(),
        ];
    }

    public function getFailedJobs(): \Illuminate\Support\Collection
    {
        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(50)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                $exception = explode("\n", $job->exception)[0] ?? '';
                return (object) [
                    'id'           => $job->id,
                    'name'         => $payload['displayName'] ?? 'Unknown',
                    'failed_at'    => $job->failed_at,
                    'exception'    => $exception,
                    'uuid'         => $job->uuid,
                ];
            });
    }

    public function getPendingJobs(): \Illuminate\Support\Collection
    {
        return DB::table('jobs')
            ->orderBy('created_at')
            ->limit(20)
            ->get()
            ->map(function ($job) {
                $payload = json_decode($job->payload, true);
                return (object) [
                    'id'         => $job->id,
                    'name'       => $payload['displayName'] ?? 'Unknown',
                    'queue'      => $job->queue,
                    'attempts'   => $job->attempts,
                    'created_at' => $job->created_at,
                ];
            });
    }

    public function retryFailed(string $uuid): void
    {
        Artisan::call('queue:retry', ['id' => [$uuid]]);
        Notification::make()->title('Job queued for retry')->success()->send();
    }

    public function retryAllFailed(): void
    {
        Artisan::call('queue:retry', ['id' => ['all']]);
        $count = DB::table('failed_jobs')->count();
        Notification::make()->title("Retrying {$count} failed jobs")->success()->send();
    }

    public function deleteFailed(string $uuid): void
    {
        DB::table('failed_jobs')->where('uuid', $uuid)->delete();
        Notification::make()->title('Failed job deleted')->success()->send();
    }

    public function clearAllFailed(): void
    {
        Artisan::call('queue:flush');
        Notification::make()->title('All failed jobs cleared')->warning()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('retry_all')
                ->label('Retry all failed')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->action('retryAllFailed'),

            Action::make('clear_all')
                ->label('Clear all failed')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action('clearAllFailed'),
        ];
    }
}
