<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $service) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category'    => ['nullable', 'integer', 'min:1'],
            'geo'         => ['nullable', 'string', 'max:10'],
            'mode'        => ['nullable', 'in:realtime,longterm'],
            'sort'        => ['nullable', 'in:score,volume,growth,recency,title'],
            'active_only' => ['nullable', 'boolean'],
            'page'        => ['nullable', 'integer', 'min:1'],
            'per_page'    => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->service->getTopics(
            categoryId: isset($validated['category']) ? (int)$validated['category'] : null,
            geo:        $validated['geo'] ?? '',
            mode:       $validated['mode'] ?? 'realtime',
            sort:       $validated['sort'] ?? 'score',
            activeOnly: filter_var($validated['active_only'] ?? false, FILTER_VALIDATE_BOOLEAN),
            perPage:    (int)($validated['per_page'] ?? 20),
            page:       (int)($validated['page'] ?? 1),
        );

        return response()->json($result);
    }

    public function live(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'geo'  => ['required', 'string', 'size:2'],
            'sort' => ['nullable', 'in:volume,growth,title'],
        ]);

        $liveMode = Setting::get('live_mode', 'disabled');

        if ($liveMode === 'disabled') {
            return response()->json([
                'error'   => 'disabled',
                'message' => 'Live mode is disabled. Enable it in Admin → Parser Settings.',
            ], 403);
        }

        try {
            return response()->json($this->service->getLiveTopics($validated['geo'], $liveMode, $validated['sort'] ?? 'volume'));
        } catch (\Throwable $e) {
            return response()->json([
                'error'   => 'parser_error',
                'message' => 'Failed to fetch live data: ' . $e->getMessage(),
            ], 503);
        }
    }

    public function categories(): JsonResponse
    {
        return response()->json($this->service->getCategories());
    }
}
