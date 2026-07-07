<?php

namespace App\Filament\Pages;

use App\Jobs\IngestTrendingJob;
use App\Models\Setting;
use App\Services\DashboardService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class ParserSettings extends Page
{
    protected string $view = 'filament.pages.parser-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Parser Settings';

    protected static ?string $title = 'Parser Settings';

    public bool   $auto_parse_enabled    = true;
    public int    $parse_interval_minutes = 30;
    public array  $manual_geos           = [];

    public function mount(): void
    {
        $this->auto_parse_enabled     = Setting::getBool('auto_parse_enabled', true);
        $this->parse_interval_minutes = Setting::getInt('parse_interval_minutes', 30);
    }

    public function save(): void
    {
        Setting::set('auto_parse_enabled', $this->auto_parse_enabled ? 'true' : 'false');
        Setting::set('parse_interval_minutes', (string) $this->parse_interval_minutes);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }

    public function parseNow(): void
    {
        $geos = empty($this->manual_geos)
            ? DashboardService::ingestGeos()
            : $this->manual_geos;

        foreach ($geos as $geo) {
            IngestTrendingJob::dispatch($geo)->onQueue('default');
        }

        Notification::make()
            ->title('Dispatched ' . count($geos) . ' ingest jobs')
            ->success()
            ->send();
    }

    public static function getGeoOptions(): array
    {
        return collect(DashboardService::ingestGeos())
            ->mapWithKeys(fn($g) => [$g => $g])
            ->all();
    }
}
