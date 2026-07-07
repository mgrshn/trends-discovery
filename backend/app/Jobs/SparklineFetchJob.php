<?php

namespace App\Jobs;

use App\Services\ParserClient;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SparklineFetchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 300;

    public function __construct(
        private readonly string $geo = '',
        private readonly int $limit = 50,
    ) {}

    public function handle(ParserClient $parser): void
    {
        $query = DB::table('topics')
            ->whereNull('sparkline')
            ->where('source', 'trending')
            ->orderByRaw('growth_pct DESC NULLS LAST')
            ->limit($this->limit)
            ->select(['id', 'keyword', 'geo']);

        if ($this->geo !== '') {
            $query->where('geo', $this->geo);
        }

        $topics = $query->get();
        $fetched = 0;

        foreach ($topics as $topic) {
            try {
                $result = $parser->trends($topic->keyword, $topic->geo, '3m', maxAttempts: 5, delaySec: 2);
                $points = $result['points'] ?? [];

                $sparkline = array_map(fn($p) => (int)($p['value'] ?? 0), $points);

                DB::table('topics')
                    ->where('id', $topic->id)
                    ->update(['sparkline' => json_encode($sparkline), 'updated_at' => now()]);

                $fetched++;
            } catch (\Throwable $e) {
                Log::debug("SparklineFetchJob: skip {$topic->keyword} ({$topic->geo}): {$e->getMessage()}");
            }
        }

        Log::info("SparklineFetchJob: fetched {$fetched} sparklines (geo={$this->geo})");
    }
}
