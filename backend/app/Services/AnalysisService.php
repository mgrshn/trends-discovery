<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class AnalysisService
{
    public function __construct(private ParserClient $parser) {}

    public function analyze(string $keyword, string $geo = '', string $period = '12m', string $engine = 'web'): array
    {
        [$trends, $related, $regions] = $this->fetchAll($keyword, $geo, $period, $engine);

        $points = $this->normalizePoints($trends['points'] ?? []);

        return [
            'keyword'        => $keyword,
            'geo'            => $geo ?: 'worldwide',
            'period'         => $period,
            'engine'         => $engine,
            'cached'         => $trends['cached'] ?? false,
            'points'         => $points,
            'related_rising' => $this->normalizeRising($related['rising'] ?? []),
            'related_top'    => $this->normalizeTop($related['top'] ?? []),
            'regions'        => $this->normalizeRegions($regions['regions'] ?? []),
        ];
    }

    private function fetchAll(string $keyword, string $geo, string $period, string $engine = 'web'): array
    {
        // Trends may need polling (202 while parser collects data from Google)
        $trends = $this->tryFetch(fn () => $this->parser->trends($keyword, $geo, $period, $engine));

        // Once trends are ready the underlying data is warm — fetch related+regions in parallel
        $baseUrl = rtrim(config('services.parser.url'), '/');
        $headers = array_filter(['X-API-Key' => config('services.parser.api_key')]);
        $params  = array_filter(['keyword' => $keyword, 'geo' => $geo, 'period' => $period]);

        $pool = Http::pool(fn ($p) => [
            $p->as('related')->withHeaders($headers)->acceptJson()
                ->get($baseUrl . '/trends/related', $params),
            $p->as('regions')->withHeaders($headers)->acceptJson()
                ->get($baseUrl . '/trends/regions', $params),
        ]);

        $related = ($pool['related']->successful()) ? $pool['related']->json() : [];
        $regions = ($pool['regions']->successful()) ? $pool['regions']->json() : [];

        return [$trends, $related, $regions];
    }

    private function tryFetch(callable $fn): array
    {
        try {
            return $fn();
        } catch (\Throwable) {
            return [];
        }
    }

    /** Convert parser points [{ts, value}] → [{date: ms_timestamp, value}] */
    private function normalizePoints(array $points): array
    {
        return array_values(array_map(function (array $p) {
            $ts = $p['ts'] ?? 0;

            if (is_string($ts)) {
                // Parser returns ISO 8601: "2025-07-06T00:00:00+00:00"
                $ms = (new \DateTime($ts))->getTimestamp() * 1000;
            } elseif ($ts > 1e10) {
                $ms = (int) $ts;           // already milliseconds
            } else {
                $ms = (int) $ts * 1000;    // seconds → milliseconds
            }

            return ['date' => $ms, 'value' => (int) ($p['value'] ?? 0)];
        }, $points));
    }

    private function normalizeRising(array $items): array
    {
        return array_values(array_map(fn ($r) => [
            'query'     => $r['query'] ?? '',
            'formatted' => $r['formatted'] ?? null,   // "+250%" or "Breakout"
            'value'     => $r['value'] ?? null,
        ], $items));
    }

    private function normalizeTop(array $items): array
    {
        return array_values(array_map(fn ($r) => [
            'query' => $r['query'] ?? '',
            'value' => $r['value'] ?? 0,
        ], $items));
    }

    private function normalizeRegions(array $items): array
    {
        $sorted = $items;
        usort($sorted, fn ($a, $b) => ($b['value'] ?? 0) <=> ($a['value'] ?? 0));

        return array_values(array_map(fn ($r) => [
            'geo_code' => $r['geo_code'] ?? '',
            'name'     => $r['name'] ?? $r['geo_code'] ?? '',
            'value'    => (int) ($r['value'] ?? 0),
        ], $sorted));
    }
}
