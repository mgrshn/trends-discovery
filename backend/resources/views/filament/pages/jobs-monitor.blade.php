<x-filament-panels::page>
    @php $stats = $this->getStats() @endphp

    {{-- Stats grid --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        @foreach ([
            ['label' => 'Pending jobs',   'value' => $stats['pending'],       'color' => 'text-blue-600'],
            ['label' => 'Failed jobs',    'value' => $stats['failed'],        'color' => $stats['failed'] > 0 ? 'text-red-600' : 'text-gray-900'],
            ['label' => 'Scored today',   'value' => $stats['scored_today'],  'color' => 'text-emerald-600'],
            ['label' => 'Last ingest',    'value' => $stats['last_ingest'],   'color' => 'text-gray-700'],
            ['label' => 'Topics total',   'value' => number_format($stats['topics_total']),  'color' => 'text-gray-700'],
            ['label' => 'Topics scored',  'value' => number_format($stats['topics_scored']), 'color' => 'text-gray-700'],
        ] as $stat)
            <x-filament::section>
                <p class="text-xs text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                <p class="mt-1 text-2xl font-bold {{ $stat['color'] }}">{{ $stat['value'] }}</p>
            </x-filament::section>
        @endforeach
    </div>

    {{-- Pending jobs --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">Pending jobs ({{ $stats['pending'] }})</x-slot>
        <x-slot name="description">First 20 in queue</x-slot>

        @php $pending = $this->getPendingJobs() @endphp

        @if ($pending->isEmpty())
            <p class="text-sm text-gray-400 py-4">Queue is empty</p>
        @else
            <div class="overflow-x-auto -mx-6 -my-4">
                <table class="w-full text-sm divide-y divide-gray-100 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-left text-xs text-gray-500">
                        <tr>
                            <th class="px-4 py-2 font-medium">Job</th>
                            <th class="px-4 py-2 font-medium">Queue</th>
                            <th class="px-4 py-2 font-medium">Attempts</th>
                            <th class="px-4 py-2 font-medium">Created</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-900">
                        @foreach ($pending as $job)
                            <tr>
                                <td class="px-4 py-2 font-mono text-xs text-gray-700 dark:text-gray-300">{{ class_basename($job->name) }}</td>
                                <td class="px-4 py-2 text-xs text-gray-500">{{ $job->queue }}</td>
                                <td class="px-4 py-2 text-xs text-gray-500">{{ $job->attempts }}</td>
                                <td class="px-4 py-2 text-xs text-gray-400">{{ \Carbon\Carbon::createFromTimestamp($job->created_at)->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    {{-- Failed jobs --}}
    <x-filament::section class="mt-6">
        <x-slot name="heading">Failed jobs ({{ $stats['failed'] }})</x-slot>

        @php $failed = $this->getFailedJobs() @endphp

        @if ($failed->isEmpty())
            <p class="text-sm text-gray-400 py-4">No failed jobs</p>
        @else
            <div class="overflow-x-auto -mx-6 -my-4">
                <table class="w-full text-sm divide-y divide-gray-100 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-left text-xs text-gray-500">
                        <tr>
                            <th class="px-4 py-2 font-medium">Job</th>
                            <th class="px-4 py-2 font-medium">Failed at</th>
                            <th class="px-4 py-2 font-medium">Exception</th>
                            <th class="px-4 py-2 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-900">
                        @foreach ($failed as $job)
                            <tr class="bg-red-50/30 dark:bg-red-950/10">
                                <td class="px-4 py-2 font-mono text-xs text-gray-700 dark:text-gray-300 whitespace-nowrap">{{ class_basename($job->name) }}</td>
                                <td class="px-4 py-2 text-xs text-gray-500 whitespace-nowrap">{{ $job->failed_at }}</td>
                                <td class="px-4 py-2 text-xs text-red-600 dark:text-red-400 max-w-md truncate">{{ $job->exception }}</td>
                                <td class="px-4 py-2 text-xs whitespace-nowrap">
                                    <div class="flex gap-2">
                                        <button
                                            wire:click="retryFailed('{{ $job->uuid }}')"
                                            class="text-blue-600 hover:text-blue-800 dark:text-blue-400 font-medium"
                                        >Retry</button>
                                        <button
                                            wire:click="deleteFailed('{{ $job->uuid }}')"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 font-medium"
                                        >Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>

    {{-- Horizon link --}}
    <div class="mt-4 text-right">
        <a href="/horizon" target="_blank"
            class="text-sm text-primary-600 hover:text-primary-700 dark:text-primary-400 inline-flex items-center gap-1">
            Open Horizon dashboard
            <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5" />
        </a>
    </div>
</x-filament-panels::page>
