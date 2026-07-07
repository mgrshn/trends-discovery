<?php

namespace App\Http\Controllers;

use App\Services\CatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function __construct(private readonly CatalogService $service) {}

    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q'        => 'nullable|string|max:200',
            'category' => 'nullable|integer|exists:categories,id',
            'status'   => 'nullable|in:exploding,regular,peaked',
            'sort'     => 'nullable|in:growth,volume,newest',
            'page'     => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $result = $this->service->getTopics(
            q:        $validated['q']        ?? '',
            category: isset($validated['category']) ? (int) $validated['category'] : null,
            status:   $validated['status']   ?? null,
            sort:     $validated['sort']     ?? 'growth',
            page:     (int) ($validated['page']     ?? 1),
            perPage:  (int) ($validated['per_page'] ?? 20),
        );

        return response()->json($result);
    }

    public function categories(): JsonResponse
    {
        return response()->json($this->service->getCategoryStats());
    }
}
