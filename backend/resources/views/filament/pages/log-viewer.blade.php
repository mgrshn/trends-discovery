<x-filament-panels::page>
    <div class="space-y-4">

        {{-- Controls --}}
        <div class="flex flex-wrap items-center gap-3">
            <select
                wire:model.live="level"
                class="rounded-lg border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white"
            >
                <option value="all">All levels</option>
                <option value="debug">Debug</option>
                <option value="info">Info</option>
                <option value="warning">Warning</option>
                <option value="error">Error</option>
                <option value="critical">Critical</option>
            </select>

            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="Search..."
                class="rounded-lg border-gray-300 text-sm w-64 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
            >

            <select
                wire:model.live="lines"
                class="rounded-lg border-gray-300 text-sm dark:bg-gray-800 dark:border-gray-700 dark:text-white"
            >
                <option value="100">Last 100</option>
                <option value="200">Last 200</option>
                <option value="500">Last 500</option>
            </select>

            <x-filament::button wire:click="clearLogs" color="danger" size="sm">
                Clear logs
            </x-filament::button>
        </div>

        {{-- Log entries --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-gray-700">
            <div class="overflow-x-auto">
                <table class="w-full text-xs font-mono">
                    <thead class="bg-gray-50 dark:bg-gray-800 text-gray-500 dark:text-gray-400">
                        <tr>
                            <th class="px-3 py-2 text-left w-36">Time</th>
                            <th class="px-3 py-2 text-left w-20">Level</th>
                            <th class="px-3 py-2 text-left">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        @forelse ($this->getLogs() as $entry)
                            @php
                                $color = match($entry['level']) {
                                    'error', 'critical', 'alert', 'emergency' => 'bg-red-50 dark:bg-red-950',
                                    'warning' => 'bg-amber-50 dark:bg-amber-950',
                                    'info'    => 'bg-blue-50 dark:bg-blue-950',
                                    default   => '',
                                };
                                $badge = match($entry['level']) {
                                    'error', 'critical' => 'text-red-700 bg-red-100',
                                    'warning'           => 'text-amber-700 bg-amber-100',
                                    'info'              => 'text-blue-700 bg-blue-100',
                                    default             => 'text-gray-700 bg-gray-100',
                                };
                            @endphp
                            <tr class="{{ $color }}">
                                <td class="px-3 py-1.5 text-gray-400 whitespace-nowrap">{{ $entry['datetime'] }}</td>
                                <td class="px-3 py-1.5">
                                    <span class="px-1.5 py-0.5 rounded text-xs {{ $badge }}">{{ $entry['level'] }}</span>
                                </td>
                                <td class="px-3 py-1.5 text-gray-800 dark:text-gray-200">
                                    <div>{{ $entry['message'] }}</div>
                                    @if ($entry['context'])
                                        <details class="mt-1">
                                            <summary class="cursor-pointer text-gray-400 hover:text-gray-600">context</summary>
                                            <pre class="mt-1 text-xs text-gray-500 whitespace-pre-wrap max-h-48 overflow-y-auto">{{ $entry['context'] }}</pre>
                                        </details>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-8 text-center text-gray-400">No log entries</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</x-filament-panels::page>
