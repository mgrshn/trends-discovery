<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PrewarmLiveTrendsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 120;

    public function __construct(
        private readonly array $keywords,
        private readonly string $geo,
    ) {}

    public function handle(): void
    {
        if (empty($this->keywords)) {
            return;
        }

        $baseUrl = rtrim(config('services.parser.url'), '/');
        $headers = array_filter(['X-API-Key' => config('services.parser.api_key')]);

        // Send up to 20 parallel requests — enough for the top of the list.
        // Parser returns 202 on first hit and caches in background; we don't wait.
        $slice = array_slice($this->keywords, 0, 20);

        Http::pool(function ($pool) use ($slice, $baseUrl, $headers) {
            foreach ($slice as $i => $keyword) {
                $pool->as("k{$i}")
                    ->withHeaders($headers)
                    ->acceptJson()
                    ->timeout(5) // fire-and-forget; 202 is fine
                    ->get($baseUrl . '/trends', array_filter([
                        'keyword' => $keyword,
                        'geo'     => $this->geo ?: null,
                        'period'  => '12m',
                    ]));
            }
        });

        Log::info('PrewarmLiveTrendsJob: kicked off ' . count($slice) . " prewarm requests for geo={$this->geo}");
    }
}
