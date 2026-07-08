<x-filament-panels::page>
    @if ($parserDown)
        <x-filament::section>
            <div class="flex items-center gap-3 text-danger-600">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5" />
                <span class="font-medium">Parser is unreachable — cannot load proxies.</span>
            </div>
        </x-filament::section>
    @elseif (empty($proxies))
        <x-filament::section>
            <p class="text-gray-500 text-sm">No proxies configured. Add one with the button above.</p>
        </x-filament::section>
    @else
        <x-filament::section>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    <thead>
                        <tr class="border-b border-gray-200 dark:border-white/10 text-xs text-gray-500 uppercase">
                            <th class="py-2 pr-4">Host</th>
                            <th class="py-2 pr-4">Protocol</th>
                            <th class="py-2 pr-4">Username</th>
                            <th class="py-2 pr-4">Stats</th>
                            <th class="py-2 pr-4">Status</th>
                            <th class="py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($proxies as $proxy)
                            <tr class="border-b border-gray-100 dark:border-white/5 hover:bg-gray-50 dark:hover:bg-white/5">
                                <td class="py-2.5 pr-4 font-mono text-xs">{{ $proxy['host'] }}</td>
                                <td class="py-2.5 pr-4">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-gray-100 dark:bg-white/10 text-gray-700 dark:text-gray-300">
                                        {{ strtoupper($proxy['protocol'] ?? 'HTTP') }}
                                    </span>
                                </td>
                                <td class="py-2.5 pr-4 text-gray-500">{{ $proxy['username'] ?? '—' }}</td>
                                <td class="py-2.5 pr-4 text-gray-500 text-xs">
                                    ✓ {{ $proxy['success_count'] ?? 0 }} &nbsp; ✗ {{ $proxy['fail_count'] ?? 0 }}
                                </td>
                                <td class="py-2.5 pr-4">
                                    @if ($proxy['enabled'] ?? true)
                                        <span class="inline-flex items-center gap-1 text-success-600 text-xs font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-success-500"></span> Enabled
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 text-gray-400 text-xs font-medium">
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-300"></span> Disabled
                                        </span>
                                    @endif
                                </td>
                                <td class="py-2.5 text-right">
                                    <div class="flex justify-end gap-2">
                                        <button
                                            wire:click="checkProxy({{ $proxy['id'] }})"
                                            class="px-2.5 py-1 text-xs rounded-lg border border-gray-200 dark:border-white/10 text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-white/10 transition-colors"
                                        >Check</button>

                                        <button
                                            wire:click="toggleProxy({{ $proxy['id'] }}, {{ $proxy['enabled'] ? 'true' : 'false' }})"
                                            class="px-2.5 py-1 text-xs rounded-lg border transition-colors
                                                {{ $proxy['enabled']
                                                    ? 'border-warning-200 text-warning-600 hover:bg-warning-50 dark:border-warning-500/30 dark:text-warning-400 dark:hover:bg-warning-500/10'
                                                    : 'border-success-200 text-success-600 hover:bg-success-50 dark:border-success-500/30 dark:text-success-400 dark:hover:bg-success-500/10'
                                                }}"
                                        >{{ $proxy['enabled'] ? 'Disable' : 'Enable' }}</button>

                                        <button
                                            wire:click="deleteProxy({{ $proxy['id'] }})"
                                            wire:confirm="Delete this proxy?"
                                            class="px-2.5 py-1 text-xs rounded-lg border border-danger-200 text-danger-600 hover:bg-danger-50 dark:border-danger-500/30 dark:text-danger-400 dark:hover:bg-danger-500/10 transition-colors"
                                        >Delete</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    @endif
</x-filament-panels::page>
