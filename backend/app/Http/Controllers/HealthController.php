<?php

namespace App\Http\Controllers;

use App\Services\ParserClient;
use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function index(ParserClient $parser): JsonResponse
    {
        $parserOk = $parser->isHealthy();

        return response()->json([
            'status' => $parserOk ? 'ok' : 'degraded',
            'service' => 'trends-discovery',
            'parser' => [
                'url' => config('services.parser.url'),
                'reachable' => $parserOk,
            ],
        ], $parserOk ? 200 : 503);
    }
}
