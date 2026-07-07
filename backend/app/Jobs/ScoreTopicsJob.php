<?php

namespace App\Jobs;

use App\Services\ParserClient;
use App\Services\ScoringService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScoreTopicsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 600;

    public function __construct(private readonly int $batchSize = 50) {}

    public function handle(ParserClient $parser, ScoringService $scoring): void
    {
        // Prioritise: never scored → high growth → recently discovered
        $topics = DB::table('topics')
            ->where('source', '!=', 'breakdown')
            ->where(fn($q) => $q
                ->whereNull('last_scored_at')
                ->orWhere('last_scored_at', '<', now()->subDays(7))
            )
            ->orderByRaw('last_scored_at IS NOT NULL, growth_pct DESC NULLS LAST, discovered_at DESC')
            ->limit($this->batchSize)
            ->get(['id', 'keyword', 'geo', 'volume']);

        if ($topics->isEmpty()) {
            Log::info('ScoreTopicsJob: nothing to score');
            return;
        }

        $scored = 0;
        $now    = now();

        foreach ($topics as $topic) {
            try {
                $result = $parser->trends($topic->keyword, $topic->geo, '12m', maxAttempts: 5, delaySec: 2);
                $points = $result['points'] ?? [];
                if (empty($points)) {
                    continue;
                }

                $m = $scoring->score($points, $topic->volume);

                DB::table('topics')->where('id', $topic->id)->update([
                    'status'         => $m['status'],
                    'score'          => $m['score'],
                    'growth_3m'      => $m['growth_3m'],
                    'growth_6m'      => $m['growth_6m'],
                    'growth_12m'     => $m['growth_12m'],
                    'last_scored_at' => $now,
                    'updated_at'     => $now,
                ]);

                DB::table('topic_metrics_history')->insert([
                    'topic_id'   => $topic->id,
                    'computed_at' => $now,
                    'score'      => $m['score'],
                    'growth_3m'  => $m['growth_3m'],
                    'growth_6m'  => $m['growth_6m'],
                    'growth_12m' => $m['growth_12m'],
                    'status'     => $m['status'],
                ]);

                $scored++;
            } catch (\Throwable $e) {
                Log::debug("ScoreTopicsJob: skip {$topic->keyword}/{$topic->geo} — {$e->getMessage()}");
            }
        }

        Log::info("ScoreTopicsJob: {$scored}/{$topics->count()} topics scored");
    }
}
