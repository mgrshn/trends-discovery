<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">Auto-parsing schedule</x-slot>
        <x-slot name="description">Configure automatic trending topics ingestion. Changes take effect on the next scheduler tick (within 1 minute).</x-slot>
        {{ $this->form }}
    </x-filament::section>
</x-filament-panels::page>
