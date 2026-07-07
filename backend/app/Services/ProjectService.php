<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProjectService
{
    public function list(): array
    {
        return DB::table('projects as p')
            ->selectRaw('p.id, p.name, p.created_at, count(pt.topic_id) as topic_count')
            ->leftJoin('project_topics as pt', 'pt.project_id', '=', 'p.id')
            ->groupBy('p.id', 'p.name', 'p.created_at')
            ->orderBy('p.created_at', 'desc')
            ->get()
            ->map(fn ($r) => [
                'id'          => $r->id,
                'name'        => $r->name,
                'topic_count' => (int) $r->topic_count,
                'created_at'  => $r->created_at,
            ])->all();
    }

    public function create(string $name): array
    {
        $id = DB::table('projects')->insertGetId([
            'name'       => $name,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $id, 'name' => $name, 'topic_count' => 0];
    }

    public function delete(int $id): void
    {
        DB::table('projects')->where('id', $id)->delete();
    }

    public function getTopics(int $projectId): array
    {
        $exists = DB::table('projects')->where('id', $projectId)->exists();
        if (!$exists) {
            throw new \RuntimeException('Project not found', 404);
        }

        return DB::table('project_topics as pt')
            ->join('topics as t', 't.id', '=', 'pt.topic_id')
            ->leftJoin('categories as c', 'c.id', '=', 't.category_id')
            ->where('pt.project_id', $projectId)
            ->select([
                't.id', 't.keyword', 't.geo',
                't.volume', 't.growth_pct', 't.growth_12m', 't.sparkline',
                't.status', 't.score',
                'c.name as category_name',
                'pt.added_at',
            ])
            ->orderBy('pt.added_at', 'desc')
            ->get()
            ->map(fn ($r) => $this->formatTopic($r))
            ->all();
    }

    public function addTopic(int $projectId, int $topicId): void
    {
        $exists = DB::table('projects')->where('id', $projectId)->exists();
        if (!$exists) {
            throw new \RuntimeException('Project not found', 404);
        }

        $topicExists = DB::table('topics')->where('id', $topicId)->exists();
        if (!$topicExists) {
            throw new \RuntimeException('Topic not found', 404);
        }

        // Ignore duplicate
        DB::table('project_topics')->insertOrIgnore([
            'project_id' => $projectId,
            'topic_id'   => $topicId,
            'added_at'   => now(),
        ]);
    }

    public function removeTopic(int $projectId, int $topicId): void
    {
        DB::table('project_topics')
            ->where('project_id', $projectId)
            ->where('topic_id', $topicId)
            ->delete();
    }

    public function exportCsv(int $projectId): string
    {
        $topics = $this->getTopics($projectId);
        $project = DB::table('projects')->where('id', $projectId)->first(['name']);

        $lines = ["keyword,geo,status,volume,growth_12m,growth_pct,category,added_at\r\n"];

        foreach ($topics as $t) {
            $lines[] = implode(',', [
                '"' . str_replace('"', '""', $t['keyword']) . '"',
                $t['geo'],
                $t['status'] ?? '',
                $t['volume']      ?? '',
                $t['growth_12m']  ?? '',
                $t['growth_pct']  ?? '',
                '"' . str_replace('"', '""', $t['category_name'] ?? '') . '"',
                $t['added_at'],
            ]) . "\r\n";
        }

        return implode('', $lines);
    }

    private function formatTopic(object $r): array
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
            'status'        => $r->status,
            'score'         => $r->score,
            'volume'        => $r->volume,
            'volume_fmt'    => $this->formatVolume($r->volume),
            'growth_pct'    => $r->growth_pct,
            'growth_12m'    => $r->growth_12m,
            'growth_fmt'    => $growthFmt,
            'sparkline'     => $sparkline,
            'category_name' => $r->category_name,
            'added_at'      => $r->added_at,
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
