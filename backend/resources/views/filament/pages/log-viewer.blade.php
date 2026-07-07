<x-filament-panels::page>
    {{-- Controls --}}
    <div class="flex flex-wrap items-center gap-3">
        <div>
            <select wire:model.live="level"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                <option value="all">All levels</option>
                <option value="debug">Debug</option>
                <option value="info">Info</option>
                <option value="warning">Warning</option>
                <option value="error">Error</option>
                <option value="critical">Critical</option>
            </select>
        </div>

        <div>
            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search…"
                class="rounded-lg border-gray-300 text-sm shadow-sm w-56 focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
        </div>

        <div>
            <select wire:model.live="lines"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:bg-gray-800 dark:border-gray-600 dark:text-white">
                <option value="100">Last 100</option>
                <option value="200">Last 200</option>
                <option value="500">Last 500</option>
            </select>
        </div>

        <x-filament::button wire:click="clearLogs" color="danger" size="sm" icon="heroicon-o-trash">
            Clear logs
        </x-filament::button>
    </div>

    {{-- Log table --}}
    <x-filament::section class="mt-4">
        <div class="overflow-x-auto -mx-6 -my-4">
            <table class="w-full text-xs font-mono divide-y divide-gray-100 dark:divide-gray-700">
                <thead>
                    <tr class="bg-gray-50 dark:bg-gray-800 text-left text-gray-500 dark:text-gray-400">
                        <th class="px-4 py-2 w-36 font-medium">Time</th>
                        <th class="px-4 py-2 w-20 font-medium">Level</th>
                        <th class="px-4 py-2 font-medium">Message</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700 bg-white dark:bg-gray-900">
                    @forelse ($this->getLogs() as $entry)
                        @php
                            $rowColor = match($entry['level']) {
                                'error', 'critical', 'alert', 'emergency' => 'bg-red-50 dark:bg-red-950/30',
                                'warning' => 'bg-amber-50 dark:bg-amber-950/30',
                                default   => '',
                            };
                            $badge = match($entry['level']) {
                                'error', 'critical', 'alert', 'emergency' => 'text-red-700 bg-red-100 dark:bg-red-900 dark:text-red-300',
                                'warning'  => 'text-amber-700 bg-amber-100 dark:bg-amber-900 dark:text-amber-300',
                                'info'     => 'text-blue-700 bg-blue-100 dark:bg-blue-900 dark:text-blue-300',
                                'debug'    => 'text-gray-500 bg-gray-100 dark:bg-gray-700 dark:text-gray-400',
                                default    => 'text-gray-600 bg-gray-100',
                            };
                        @endphp
                        <tr class="{{ $rowColor }}">
                            <td class="px-4 py-1.5 text-gray-400 whitespace-nowrap">{{ $entry['datetime'] }}</td>
                            <td class="px-4 py-1.5">
                                <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase {{ $badge }}">
                                    {{ $entry['level'] }}
                                </span>
                            </td>
                            <td class="px-4 py-1.5 text-gray-800 dark:text-gray-200 max-w-2xl">
                                <div class="truncate">{{ $entry['message'] }}</div>
                                @if ($entry['context'])
                                    <details class="mt-0.5">
                                        <summary class="cursor-pointer text-gray-400 hover:text-gray-600 text-[10px]">stack trace</summary>
                                        <pre class="mt-1 text-[10px] text-gray-500 whitespace-pre-wrap max-h-40 overflow-y-auto">{{ $entry['context'] }}</pre>
                                    </details>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-10 text-center text-gray-400">No log entries</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-filament::section>
</x-filament-panels::page>
