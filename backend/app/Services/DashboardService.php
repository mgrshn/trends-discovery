<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const INGEST_GEOS = [
        'US', 'GB', 'CA', 'AU', 'DE', 'FR', 'IN', 'BR', 'MX', 'JP',
        'KR', 'SG', 'NL', 'SE', 'NO', 'ES', 'IT', 'PL', 'RU', 'UA',
        'ZA', 'NG', 'EG', 'AR', 'CL', 'CO', 'ID', 'PH', 'TH', 'VN',
        'NZ', 'CH', 'AT', 'BE', 'PT', 'CZ', 'HU', 'RO', 'TR', 'IL',
        'SA', 'AE', 'PK', 'BD', 'MY', 'HK', 'TW', 'GR', 'DK', 'FI', 'IE',
    ];

    public static function ingestGeos(): array
    {
        return self::INGEST_GEOS;
    }

    public function getTopics(
        ?int $categoryId = null,
        string $geo = '',
        string $mode = 'realtime',
        string $sort = 'score',
        bool $activeOnly = false,
        int $perPage = 20,
        int $page = 1,
    ): array {
        if ($mode === 'longterm') {
            return $this->getLongtermTopics($categoryId, $geo, $sort, $activeOnly, $perPage, $page);
        }

        $query = DB::table('topics as t')
            ->leftJoin('categories as c', 'c.id', '=', 't.category_id')
            ->where('t.source', 'trending')
            ->where(fn($q) => $q->whereNull('t.approved')->orWhere('t.approved', true))
            ->select([
                't.id', 't.keyword', 't.geo',
                't.volume', 't.growth_pct', 't.growth_3m', 't.growth_6m', 't.growth_12m',
                't.sparkline', 't.status', 't.score', 't.updated_at',
                'c.id as category_id', 'c.name as category_name',
            ]);

        if ($categoryId !== null) {
            $query->where('t.category_id', $categoryId);
        }

        if ($geo !== '') {
            $query->where('t.geo', $geo);
        }

        if ($activeOnly) {
            // Real-time "active" = seen within last 48 hours
            $query->where('t.updated_at', '>=', now()->subHours(48));
        }

        $query->orderByRaw(match($sort) {
            'volume'  => 't.volume DESC NULLS LAST, t.growth_pct DESC NULLS LAST',
            'growth'  => 't.growth_pct DESC NULLS LAST, t.volume DESC NULLS LAST',
            'recency' => 't.discovered_at DESC, t.growth_pct DESC NULLS LAST',
            'title'   => 't.keyword ASC',
            default   => 't.growth_pct DESC NULLS LAST, t.volume DESC NULLS LAST',
        });

        $total = (clone $query)->count();
        $items = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return [
            'data'     => $items->map(fn($r) => $this->formatTopic($r))->values()->all(),
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'mode'     => 'realtime',
        ];
    }

    private function getLongtermTopics(?int $categoryId, string $geo, string $sort, bool $activeOnly, int $perPage, int $page): array
    {
        $query = DB::table('topics as t')
            ->leftJoin('categories as c', 'c.id', '=', 't.category_id')
            ->whereNotNull('t.last_scored_at')
            ->where('t.status', '!=', 'noise')
            ->where(fn($q) => $q->whereNull('t.approved')->orWhere('t.approved', true))
            ->select([
                't.id', 't.keyword', 't.geo',
                't.volume', 't.growth_pct', 't.growth_3m', 't.growth_6m', 't.growth_12m',
                't.sparkline', 't.status', 't.score', 't.last_scored_at', 't.updated_at',
                'c.id as category_id', 'c.name as category_name',
            ]);

        if ($categoryId !== null) {
            $query->where('t.category_id', $categoryId);
        }

        if ($geo !== '') {
            $query->where('t.geo', $geo);
        }

        // "Active" in long-term = only exploding or regular (not peaked)
        if ($activeOnly) {
            $query->whereIn('t.status', ['exploding', 'regular']);
        }

        $orderBy = match($sort) {
            'volume'  => 't.volume DESC NULLS LAST, t.score DESC NULLS LAST',
            'growth'  => 'COALESCE(t.growth_12m, t.growth_pct) DESC NULLS LAST',
            'recency' => 't.last_scored_at DESC, t.score DESC NULLS LAST',
            'title'   => 't.keyword ASC',
            // Default: status priority + score
            default   => "CASE t.status WHEN 'exploding' THEN 1 WHEN 'regular' THEN 2 WHEN 'peaked' THEN 3 ELSE 4 END, t.score DESC NULLS LAST, t.volume DESC NULLS LAST",
        };

        $query->orderByRaw($orderBy);

        $total = (clone $query)->count();
        $items = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return [
            'data'     => $items->map(fn($r) => $this->formatTopic($r))->values()->all(),
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
            'mode'     => 'longterm',
        ];
    }

    public function getCategories(): array
    {
        return DB::table('categories')
            ->select('id', 'name')
            ->orderBy('name')
            ->get()
            ->map(fn($r) => ['id' => $r->id, 'name' => $r->name])
            ->all();
    }

    private function formatTopic(object $r): array
    {
        $sparkline = $r->sparkline ? json_decode($r->sparkline, true) : null;

        // For longterm cards, prefer scored growth_12m over raw growth_pct
        $displayGrowth = property_exists($r, 'growth_12m') && $r->growth_12m !== null
            ? (float) $r->growth_12m
            : (float) ($r->growth_pct ?? 0);

        $growthFmt = $displayGrowth >= 0
            ? '+' . number_format($displayGrowth) . '%'
            : number_format($displayGrowth) . '%';

        return [
            'id'            => $r->id,
            'keyword'       => $r->keyword,
            'geo'           => $r->geo,
            'volume'        => $r->volume,
            'volume_fmt'    => $this->formatVolume($r->volume),
            'growth_pct'    => $r->growth_pct,
            'growth_fmt'    => $growthFmt,
            'growth_3m'     => property_exists($r, 'growth_3m')  ? $r->growth_3m  : null,
            'growth_6m'     => property_exists($r, 'growth_6m')  ? $r->growth_6m  : null,
            'growth_12m'    => property_exists($r, 'growth_12m') ? $r->growth_12m : null,
            'sparkline'     => $sparkline,
            'status'        => $r->status,
            'score'         => property_exists($r, 'score') ? $r->score : null,
            'category_id'   => $r->category_id,
            'category_name' => $r->category_name,
            'updated_at'    => property_exists($r, 'updated_at')    ? $r->updated_at    : null,
            'last_scored_at' => property_exists($r, 'last_scored_at') ? $r->last_scored_at : null,
        ];
    }

    private function formatVolume(?int $volume): ?string
    {
        if ($volume === null) {
            return null;
        }
        if ($volume >= 1_000_000) {
            return round($volume / 1_000_000, 1) . 'M';
        }
        if ($volume >= 1_000) {
            return round($volume / 1_000, 1) . 'K';
        }
        return (string) $volume;
    }
}
