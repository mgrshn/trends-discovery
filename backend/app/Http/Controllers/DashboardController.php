<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $service) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category' => ['nullable', 'integer', 'min:1'],
            'geo'      => ['nullable', 'string', 'max:10'],
            'mode'     => ['nullable', 'in:realtime,longterm'],
            'page'     => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->service->getTopics(
            categoryId: isset($validated['category']) ? (int)$validated['category'] : null,
            geo:        $validated['geo'] ?? '',
            mode:       $validated['mode'] ?? 'realtime',
            perPage:    (int)($validated['per_page'] ?? 20),
            page:       (int)($validated['page'] ?? 1),
        );

        return response()->json($result);
    }

    public function categories(): JsonResponse
    {
        return response()->json($this->service->getCategories());
    }
}
