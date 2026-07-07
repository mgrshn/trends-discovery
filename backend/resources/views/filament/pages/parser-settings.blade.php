<x-filament-panels::page>
    <div class="space-y-6">

        {{-- Auto-parse settings --}}
        <x-filament::section>
            <x-slot name="heading">Auto-parsing</x-slot>
            <x-slot name="description">Control the automatic trending topics ingestion schedule.</x-slot>

            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">Enable auto-parsing</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Automatically fetch trending topics on a schedule</p>
                    </div>
                    <x-filament::input.wrapper>
                        <input
                            type="checkbox"
                            wire:model.live="auto_parse_enabled"
                            class="rounded border-gray-300 text-primary-600 shadow-sm focus:ring-primary-600"
                        >
                    </x-filament::input.wrapper>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                        Parse interval (minutes)
                    </label>
                    <select
                        wire:model="parse_interval_minutes"
                        class="block w-48 rounded-lg border-gray-300 text-sm shadow-sm focus:ring-primary-600 focus:border-primary-600 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                    >
                        @foreach ([5, 10, 15, 30, 45, 60] as $m)
                            <option value="{{ $m }}">Every {{ $m }} min</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <x-filament::button wire:click="save" color="primary">
                        Save settings
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        {{-- Manual parse --}}
        <x-filament::section>
            <x-slot name="heading">Parse Now</x-slot>
            <x-slot name="description">Dispatch a one-time ingest job for selected countries (empty = all 51 geos).</x-slot>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-1">
                        Select geos (leave empty for all)
                    </label>
                    <select
                        wire:model="manual_geos"
                        multiple
                        class="block w-full h-48 rounded-lg border-gray-300 text-sm shadow-sm focus:ring-primary-600 focus:border-primary-600 dark:bg-gray-800 dark:border-gray-700 dark:text-white"
                    >
                        @foreach (App\Filament\Pages\ParserSettings::getGeoOptions() as $code => $label)
                            <option value="{{ $code }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-400 mt-1">Hold Cmd/Ctrl to select multiple</p>
                </div>

                <div>
                    <x-filament::button wire:click="parseNow" color="success">
                        Parse now
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

    </div>
</x-filament-panels::page>
