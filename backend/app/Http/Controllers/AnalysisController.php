<?php

namespace App\Http\Controllers;

use App\Services\AnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalysisController extends Controller
{
    public function show(Request $request, AnalysisService $service): JsonResponse
    {
        $validated = $request->validate([
            'keyword' => 'required|string|max:200',
            'geo'     => 'nullable|string|max:10',
            'period'  => 'nullable|in:1m,3m,12m,5y,all',
            'engine'  => 'nullable|in:web,youtube,images,news,shopping',
        ]);

        $data = $service->analyze(
            keyword: $validated['keyword'],
            geo:     $validated['geo'] ?? '',
            period:  $validated['period'] ?? '12m',
            engine:  $validated['engine'] ?? 'web',
        );

        return response()->json($data);
    }
}
