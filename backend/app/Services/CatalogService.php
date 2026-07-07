<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class CatalogService
{
    private const PER_PAGE_DEFAULT = 20;

    public function getCategoryStats(): array
    {
        $rows = DB::table('topics as t')
            ->join('categories as c', 'c.id', '=', 't.category_id')
            ->where(fn ($q) => $q->whereNull('t.approved')->orWhere('t.approved', true))
            ->where('t.status', '!=', 'noise')
            ->selectRaw('c.id, c.name, count(t.id) as total')
            ->groupBy('c.id', 'c.name')
            ->orderBy('c.name')
            ->get();

        return $rows->map(fn ($r) => [
            'id'    => $r->id,
            'name'  => $r->name,
            'total' => (int) $r->total,
        ])->values()->all();
    }

    public function getTopics(
        string  $q         = '',
        ?int    $category  = null,
        ?string $status    = null,
        string  $sort      = 'growth',
        int     $page      = 1,
        int     $perPage   = self::PER_PAGE_DEFAULT,
    ): array {
        $query = DB::table('topics as t')
            ->leftJoin('categories as c', 'c.id', '=', 't.category_id')
            ->where(fn ($q2) => $q2->whereNull('t.approved')->orWhere('t.approved', true))
            ->where('t.status', '!=', 'noise')
            ->whereNotIn('t.source', ['breakdown'])
            ->select([
                't.id', 't.keyword', 't.geo', 't.source',
                't.volume', 't.growth_pct', 't.growth_3m', 't.growth_6m', 't.growth_12m',
                't.sparkline', 't.status', 't.score',
                'c.id as category_id', 'c.name as category_name',
            ]);

        if ($q !== '') {
            $query->whereRaw('t.keyword ILIKE ?', ['%' . $q . '%']);
        }

        if ($category !== null) {
            $query->where('t.category_id', $category);
        }

        if ($status !== null && in_array($status, ['exploding', 'regular', 'peaked'], true)) {
            $query->where('t.status', $status);
        }

        match ($sort) {
            'volume'  => $query->orderByRaw('t.volume DESC NULLS LAST, COALESCE(t.growth_12m, t.growth_pct) DESC NULLS LAST'),
            'newest'  => $query->orderByRaw('t.discovered_at DESC NULLS LAST, t.id DESC'),
            default   => $query->orderByRaw('COALESCE(t.growth_12m, t.growth_pct) DESC NULLS LAST, t.volume DESC NULLS LAST'),
        };

        $total = (clone $query)->count();
        $items = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        return [
            'data'     => $items->map(fn ($r) => $this->formatRow($r))->values()->all(),
            'total'    => $total,
            'page'     => $page,
            'per_page' => $perPage,
        ];
    }

    private function formatRow(object $r): array
    {
        $sparkline = $r->sparkline ? json_decode($r->sparkline, true) : null;

        $growth = (float) ($r->growth_12m ?? $r->growth_pct ?? 0);
        $growthFmt = $growth >= 0
            ? '+' . number_format($growth) . '%'
            : number_format($growth) . '%';

        return [
            'id'            => $r->id,
            'keyword'       => $r->keyword,
            'geo'           => $r->geo,
            'source'        => $r->source,
            'volume'        => $r->volume,
            'volume_fmt'    => $this->formatVolume($r->volume),
            'growth_pct'    => $r->growth_pct,
            'growth_12m'    => $r->growth_12m,
            'growth_fmt'    => $growthFmt,
            'sparkline'     => $sparkline,
            'status'        => $r->status,
            'score'         => $r->score,
            'category_id'   => $r->category_id,
            'category_name' => $r->category_name,
        ];
    }

    private function formatVolume(?int $volume): ?string
    {
        if ($volume === null) return null;
        if ($volume >= 1_000_000) return round($volume / 1_000_000, 1) . 'M';
        if ($volume >= 1_000)     return round($volume / 1_000, 1) . 'K';
        return (string) $volume;
    }
}
