<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class DashboardService
{
    private const INGEST_GEOS = ['US', 'GB', 'AU', 'CA', 'IN', 'DE', 'FR'];

    public static function ingestGeos(): array
    {
        return self::INGEST_GEOS;
    }

    public function getTopics(
        ?int $categoryId = null,
        string $geo = '',
        string $mode = 'realtime',
        int $perPage = 20,
        int $page = 1,
    ): array {
        if ($mode === 'longterm') {
            // Этап 3 (скоринг) ещё не реализован — возвращаем пустой placeholder
            return ['data' => [], 'total' => 0, 'page' => $page, 'per_page' => $perPage, 'mode' => 'longterm'];
        }

        $query = DB::table('topics as t')
            ->leftJoin('categories as c', 'c.id', '=', 't.category_id')
            ->where('t.source', 'trending')
            ->where(fn($q) => $q->whereNull('t.approved')->orWhere('t.approved', true))
            ->select([
                't.id', 't.keyword', 't.geo',
                't.volume', 't.growth_pct', 't.sparkline', 't.status',
                'c.id as category_id', 'c.name as category_name',
            ]);

        if ($categoryId !== null) {
            $query->where('t.category_id', $categoryId);
        }

        if ($geo !== '') {
            $query->where('t.geo', $geo);
        }

        $query->orderByRaw('t.growth_pct DESC NULLS LAST, t.volume DESC NULLS LAST');

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

        return [
            'id'            => $r->id,
            'keyword'       => $r->keyword,
            'geo'           => $r->geo,
            'volume'        => $r->volume,
            'volume_fmt'    => $this->formatVolume($r->volume),
            'growth_pct'    => $r->growth_pct,
            'growth_fmt'    => $r->growth_pct !== null
                ? '+' . number_format((float)$r->growth_pct) . '%'
                : null,
            'sparkline'     => $sparkline,
            'status'        => $r->status,
            'category_id'   => $r->category_id,
            'category_name' => $r->category_name,
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
