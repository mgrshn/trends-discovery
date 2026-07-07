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

class IngestTrendingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function __construct(private readonly string $geo = 'US') {}

    public function handle(ParserClient $parser): void
    {
        $result = $parser->trending(geo: $this->geo, sinceHours: 24, limit: 100, sort: 'growth');
        $trends = $result['trends'] ?? [];

        if (empty($trends)) {
            return;
        }

        $categoryMap = $this->buildCategoryMap();
        $now = now();
        $upserted = 0;

        foreach ($trends as $trend) {
            $keyword = trim($trend['keyword'] ?? '');
            if ($keyword === '') {
                continue;
            }

            $categoryId = $this->resolveCategoryId($trend['categories'] ?? [], $categoryMap);

            DB::table('topics')->upsert([
                'keyword'       => $keyword,
                'geo'           => $this->geo,
                'source'        => 'trending',
                'category_id'   => $categoryId,
                'volume'        => $trend['volume'] ?? null,
                'growth_pct'    => $trend['growth_pct'] ?? null,
                'discovered_at' => $now,
                'created_at'    => $now,
                'updated_at'    => $now,
            ], ['keyword', 'geo'], ['volume', 'growth_pct', 'category_id', 'updated_at']);

            $upserted++;

            foreach ($trend['breakdown'] ?? [] as $bk) {
                $bk = trim($bk);
                if ($bk === '' || strtolower($bk) === strtolower($keyword)) {
                    continue;
                }

                DB::table('topics')->upsert([
                    'keyword'       => $bk,
                    'geo'           => $this->geo,
                    'source'        => 'breakdown',
                    'seed_keyword'  => $keyword,
                    'category_id'   => $categoryId,
                    'discovered_at' => $now,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ], ['keyword', 'geo'], ['updated_at']);
            }
        }

        Log::info("IngestTrendingJob: {$upserted} trends upserted for geo={$this->geo}");

        // Queue sparkline fetch for this geo after ingestion
        SparklineFetchJob::dispatch($this->geo)->onQueue('default');
    }

    private function buildCategoryMap(): array
    {
        $map = [];
        $rows = DB::table('categories')->select('id', 'google_category_ids')->get();
        foreach ($rows as $row) {
            $ids = json_decode($row->google_category_ids, true) ?? [];
            foreach ($ids as $googleId) {
                $map[(int)$googleId] = (int)$row->id;
            }
        }
        return $map;
    }

    private function resolveCategoryId(array $googleIds, array $map): ?int
    {
        foreach ($googleIds as $gid) {
            if (isset($map[(int)$gid])) {
                return $map[(int)$gid];
            }
        }
        return null;
    }
}
