<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Response;

class ParserClient
{
    private PendingRequest $http;

    public function __construct()
    {
        $this->http = Http::baseUrl(config('services.parser.url'))
            ->withHeaders(array_filter([
                'X-API-Key' => config('services.parser.api_key'),
            ]))
            ->timeout(35)
            ->acceptJson();
    }

    public function isHealthy(): bool
    {
        try {
            return $this->http->get('/health')->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * GET /trends — time series for a keyword.
     * Returns points array or throws on error.
     * Retries on 202 (data is being collected) up to $maxAttempts times with $delaySec pause.
     */
    public function trends(
        string $keyword,
        string $geo = '',
        string $period = '12m',
        string $engine = 'web',
        int $category = 0,
        int $maxAttempts = 30,
        int $delaySec = 2,
    ): array {
        $params = array_filter([
            'keyword'  => $keyword,
            'geo'      => $geo,
            'period'   => $period,
            'engine'   => $engine,
            'category' => $category ?: null,
        ]);

        return $this->pollUntilReady(fn () => $this->http->get('/trends', $params), $maxAttempts, $delaySec);
    }

    /**
     * GET /trends/related — related queries (top + rising).
     */
    public function related(string $keyword, string $geo = '', string $period = '12m'): array
    {
        $params = array_filter(['keyword' => $keyword, 'geo' => $geo, 'period' => $period]);

        return $this->pollUntilReady(fn () => $this->http->get('/trends/related', $params));
    }

    /**
     * GET /trends/regions — interest by region.
     */
    public function regions(string $keyword, string $geo = '', string $period = '12m'): array
    {
        $params = array_filter(['keyword' => $keyword, 'geo' => $geo, 'period' => $period]);

        return $this->pollUntilReady(fn () => $this->http->get('/trends/regions', $params));
    }

    /**
     * GET /trends/batch — up to 20 keywords × N geos at once.
     */
    public function batch(array $keywords, array $geos = [], string $period = '12m'): array
    {
        $params = [
            'keywords' => implode(',', $keywords),
            'period'   => $period,
        ];
        if ($geos) {
            $params['geos'] = implode(',', $geos);
        }

        return $this->http->get('/trends/batch', $params)->json();
    }

    /**
     * GET /trending — Trending Now for a geo.
     */
    public function trending(
        string $geo = 'US',
        int $sinceHours = 24,
        int $limit = 50,
        string $sort = 'volume',
        int $category = 0,
    ): array {
        $params = array_filter([
            'geo'         => $geo,
            'since_hours' => $sinceHours,
            'limit'       => $limit,
            'sort'        => $sort,
            'category'    => $category ?: null,
        ]);

        return $this->http->get('/trending', $params)->json();
    }

    // ── Proxy management ─────────────────────────────────────────────────────

    public function getProxies(): array
    {
        return $this->http->get('/proxies')->throw()->json() ?? [];
    }

    public function addProxy(string $url): array
    {
        return $this->http->post('/proxies', ['url' => $url])->throw()->json();
    }

    public function toggleProxy(int $id, bool $enabled): array
    {
        return $this->http->patch("/proxies/{$id}", ['enabled' => $enabled])->throw()->json();
    }

    public function checkProxy(int $id): array
    {
        return $this->http->post("/proxies/{$id}/check")->throw()->json();
    }

    public function deleteProxy(int $id): void
    {
        $this->http->delete("/proxies/{$id}")->throw();
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    /**
     * Poll endpoint until it returns non-202, up to $maxAttempts × $delaySec.
     */
    private function pollUntilReady(callable $request, int $maxAttempts = 30, int $delaySec = 2): array
    {
        for ($i = 0; $i < $maxAttempts; $i++) {
            /** @var Response $response */
            $response = $request();

            if ($response->status() === 202) {
                sleep($delaySec);
                continue;
            }

            $response->throw();

            return $response->json();
        }

        throw new \RuntimeException('Parser did not respond in time (202 timeout).');
    }
}
