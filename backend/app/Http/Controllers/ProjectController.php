<?php

namespace App\Http\Controllers;

use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProjectController extends Controller
{
    public function __construct(private readonly ProjectService $service) {}

    public function index(): JsonResponse
    {
        return response()->json($this->service->list());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $project = $this->service->create($validated['name']);

        return response()->json($project, 201);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->delete($id);
        return response()->json(['ok' => true]);
    }

    public function topics(int $id): JsonResponse
    {
        try {
            $topics = $this->service->getTopics($id);
            return response()->json($topics);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], (int) $e->getCode() ?: 404);
        }
    }

    public function addTopic(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'topic_id' => 'required|integer|exists:topics,id',
        ]);

        try {
            $this->service->addTopic($id, (int) $validated['topic_id']);
            return response()->json(['ok' => true]);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], (int) $e->getCode() ?: 404);
        }
    }

    public function removeTopic(int $id, int $topicId): JsonResponse
    {
        $this->service->removeTopic($id, $topicId);
        return response()->json(['ok' => true]);
    }

    public function export(int $id): Response
    {
        try {
            $csv = $this->service->exportCsv($id);
            return response($csv, 200, [
                'Content-Type'        => 'text/csv',
                'Content-Disposition' => "attachment; filename=\"project-{$id}-trends.csv\"",
            ]);
        } catch (\RuntimeException $e) {
            return response('Not found', 404);
        }
    }
}
