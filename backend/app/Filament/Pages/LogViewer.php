<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class LogViewer extends Page
{
    protected string $view = 'filament.pages.log-viewer';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Logs';

    protected static ?string $title = 'Application Logs';

    public string $level  = 'all';
    public int    $lines  = 200;
    public string $search = '';

    public function getLogs(): array
    {
        $logFile = storage_path('logs/laravel.log');

        if (!file_exists($logFile)) {
            return [];
        }

        $content = file_get_contents($logFile);
        $rawLines = array_reverse(explode("\n", $content));

        $entries  = [];
        $current  = null;

        foreach ($rawLines as $line) {
            if (preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.(\w+): (.+)$/', $line, $m)) {
                if ($current) {
                    $entries[] = $current;
                }
                $current = [
                    'datetime' => $m[1],
                    'level'    => strtolower($m[2]),
                    'message'  => $m[3],
                    'context'  => '',
                ];
            } elseif ($current && trim($line) !== '') {
                $current['context'] = trim($line) . "\n" . $current['context'];
            }
        }

        if ($current) {
            $entries[] = $current;
        }

        // Filter by level
        if ($this->level !== 'all') {
            $entries = array_filter($entries, fn($e) => $e['level'] === $this->level);
        }

        // Filter by search
        if ($this->search !== '') {
            $q = strtolower($this->search);
            $entries = array_filter($entries, fn($e) =>
                str_contains(strtolower($e['message']), $q) ||
                str_contains(strtolower($e['context']), $q)
            );
        }

        return array_slice(array_values($entries), 0, $this->lines);
    }

    public function clearLogs(): void
    {
        $logFile = storage_path('logs/laravel.log');
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        \Filament\Notifications\Notification::make()
            ->title('Logs cleared')
            ->success()
            ->send();
    }
}
