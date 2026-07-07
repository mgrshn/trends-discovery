<?php

namespace App\Jobs;

use App\Services\ParserClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RelatedRisingIngestJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;
    public int $tries   = 2;

    public function __construct(
        private readonly string $keyword,
        private readonly string $geo,
    ) {}

    public function handle(ParserClient $parser): void
    {
        try {
            $result  = $parser->related($this->keyword, $this->geo, '12m');
            $rising  = $result['rising'] ?? [];
        } catch (\Throwable $e) {
            Log::warning("RelatedRisingIngestJob failed for {$this->keyword}/{$this->geo}: {$e->getMessage()}");
            return;
        }

        if (empty($rising)) {
            return;
        }

        $now = now();

        foreach (array_slice($rising, 0, 15) as $item) {
            $keyword   = trim((string) ($item['query'] ?? ''));
            $growthRaw = (int) ($item['value'] ?? 0);

            if ($keyword === '' || $growthRaw <= 0) {
                continue;
            }

            // Unique constraint is (keyword, geo) — update if exists, insert if new
            $existing = DB::table('topics')
                ->where('keyword', $keyword)
                ->where('geo', $this->geo)
                ->first(['id', 'source']);

            if ($existing) {
                // Only update growth info; don't overwrite source or other metadata
                DB::table('topics')->where('id', $existing->id)->update([
                    'growth_pct'   => $growthRaw,
                    'seed_keyword' => $existing->source === 'related_rising' ? $this->keyword : null,
                    'updated_at'   => $now,
                ]);
            } else {
                DB::table('topics')->insert([
                    'keyword'       => $keyword,
                    'geo'           => $this->geo,
                    'source'        => 'related_rising',
                    'seed_keyword'  => $this->keyword,
                    'growth_pct'    => $growthRaw,
                    'discovered_at' => $now,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }
        }
    }
}
