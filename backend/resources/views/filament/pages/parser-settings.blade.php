<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Auto-parsing schedule</x-slot>
        {{ $this->form }}
    </x-filament::section>

    <x-filament::section class="mt-6">
        <x-slot name="heading">Parse now</x-slot>
        <x-slot name="description">Dispatch a one-time ingest for selected countries. Leave empty to run all 51 geos.</x-slot>

        <div class="space-y-4">
            <div>
                <label class="fi-fo-field-wrp-label inline-flex items-center gap-x-3 text-sm font-medium leading-6 text-gray-950 dark:text-white">
                    Select geos
                </label>
                <select
                    wire:model="manual_geos"
                    multiple
                    class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-primary-500 focus:outline-none focus:ring-1 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white h-48"
                >
                    @foreach (App\Filament\Pages\ParserSettings::getGeoOptions() as $code => $label)
                        <option value="{{ $code }}">{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-gray-500">Hold Cmd/Ctrl to select multiple</p>
            </div>

            <x-filament::button wire:click="parseNow" color="success">
                Parse now
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>
