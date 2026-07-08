<x-filament-panels::page>
    @if ($parserDown)
        <x-filament::section>
            <div class="flex items-center gap-3 text-danger-600">
                <x-heroicon-o-exclamation-triangle class="w-5 h-5 flex-shrink-0" />
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
                        <tr style="border-bottom: 1px solid #e5e7eb;" class="text-xs text-gray-500 uppercase">
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
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                                <td class="py-2.5 pr-4 font-mono text-xs text-gray-700">{{ $proxy['host'] }}</td>
                                <td class="py-2.5 pr-4">
                                    <span class="inline-flex px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                        {{ strtoupper($proxy['protocol'] ?? 'HTTP') }}
                                    </span>
                                </td>
                                <td class="py-2.5 pr-4 text-gray-500 text-xs">{{ $proxy['username'] ?? '—' }}</td>
                                <td class="py-2.5 pr-4 text-gray-500 text-xs">
                                    ✓ {{ $proxy['success_count'] ?? 0 }} &nbsp; ✗ {{ $proxy['fail_count'] ?? 0 }}
                                </td>
                                <td class="py-2.5 pr-4 text-xs">
                                    @if ($proxy['enabled'] ?? true)
                                        <span class="text-success-600 font-medium">● Enabled</span>
                                    @else
                                        <span class="text-gray-400 font-medium">○ Disabled</span>
                                    @endif
                                </td>
                                <td class="py-2.5">
                                    <div class="flex justify-end gap-2">
                                        <button wire:click="checkProxy({{ $proxy['id'] }})"
                                            style="padding: 3px 10px; font-size: 12px; border-radius: 8px; border: 1px solid #d1d5db; color: #4b5563; cursor: pointer; background: white;"
                                            onmouseover="this.style.background='#f9fafb'" onmouseout="this.style.background='white'"
                                        >Check</button>

                                        <button wire:click="toggleProxy({{ $proxy['id'] }}, {{ $proxy['enabled'] ? 'true' : 'false' }})"
                                            style="padding: 3px 10px; font-size: 12px; border-radius: 8px; border: 1px solid {{ ($proxy['enabled'] ?? true) ? '#fcd34d' : '#86efac' }}; color: {{ ($proxy['enabled'] ?? true) ? '#d97706' : '#16a34a' }}; cursor: pointer; background: white;"
                                        >{{ ($proxy['enabled'] ?? true) ? 'Disable' : 'Enable' }}</button>

                                        <button wire:click="deleteProxy({{ $proxy['id'] }})"
                                            wire:confirm="Delete this proxy?"
                                            style="padding: 3px 10px; font-size: 12px; border-radius: 8px; border: 1px solid #fca5a5; color: #dc2626; cursor: pointer; background: white;"
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
