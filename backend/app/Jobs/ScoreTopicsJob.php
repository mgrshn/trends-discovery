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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ScoreTopicsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 900;

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

        $pointsMap = $this->fetchParallel($topics->all());

        $scored = 0;
        $now    = now();

        foreach ($topics as $topic) {
            $points = $pointsMap[$topic->id] ?? [];
            if (empty($points)) {
                continue;
            }

            try {
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
                    'topic_id'    => $topic->id,
                    'computed_at' => $now,
                    'score'       => $m['score'],
                    'growth_3m'   => $m['growth_3m'],
                    'growth_6m'   => $m['growth_6m'],
                    'growth_12m'  => $m['growth_12m'],
                    'status'      => $m['status'],
                ]);

                $scored++;
            } catch (\Throwable $e) {
                Log::debug("ScoreTopicsJob: skip {$topic->keyword}/{$topic->geo} — {$e->getMessage()}");
            }
        }

        Log::info("ScoreTopicsJob: {$scored}/{$topics->count()} topics scored");
    }

    /** Fetch trend points for all topics in parallel, retrying 202s up to 5 times. */
    private function fetchParallel(array $topics): array
    {
        $baseUrl = rtrim(config('services.parser.url'), '/');
        $headers = array_filter(['X-API-Key' => config('services.parser.api_key')]);

        $pending = $topics;
        $results = [];

        for ($attempt = 0; $attempt < 5 && !empty($pending); $attempt++) {
            if ($attempt > 0) {
                sleep(2);
            }

            $responses = Http::pool(function ($pool) use ($pending, $baseUrl, $headers) {
                foreach ($pending as $topic) {
                    $pool->as('t' . $topic->id)
                        ->withHeaders($headers)
                        ->acceptJson()
                        ->timeout(35)
                        ->get($baseUrl . '/trends', array_filter([
                            'keyword' => $topic->keyword,
                            'geo'     => $topic->geo,
                            'period'  => '12m',
                        ]));
                }
            });

            $stillPending = [];
            foreach ($pending as $topic) {
                $key      = 't' . $topic->id;
                $response = $responses[$key];

                if ($response instanceof \Throwable) {
                    continue;
                }

                if ($response->status() === 202) {
                    $stillPending[] = $topic;
                } elseif ($response->successful()) {
                    $results[$topic->id] = $response->json('points', []);
                }
            }

            $pending = $stillPending;
        }

        return $results;
    }
}
