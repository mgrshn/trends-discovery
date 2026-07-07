<?php

namespace App\Filament\Pages;

use App\Jobs\IngestTrendingJob;
use App\Models\Setting;
use App\Services\DashboardService;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use BackedEnum;

class ParserSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.parser-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Parser Settings';

    protected static ?string $title = 'Parser Settings';

    public ?array $data = [];
    public array  $manual_geos = [];

    public function mount(): void
    {
        $this->form->fill([
            'auto_parse_enabled'     => Setting::getBool('auto_parse_enabled', true),
            'parse_interval_minutes' => Setting::getInt('parse_interval_minutes', 30),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Auto-parsing schedule')
                    ->description('Control automatic trending topics ingestion.')
                    ->schema([
                        Toggle::make('auto_parse_enabled')
                            ->label('Enable auto-parsing')
                            ->helperText('Automatically fetch trending topics on the configured interval.'),

                        Select::make('parse_interval_minutes')
                            ->label('Parse interval')
                            ->options([
                                5  => 'Every 5 minutes',
                                10 => 'Every 10 minutes',
                                15 => 'Every 15 minutes',
                                30 => 'Every 30 minutes',
                                45 => 'Every 45 minutes',
                                60 => 'Every 60 minutes',
                            ])
                            ->native(false),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        Setting::set('auto_parse_enabled', $state['auto_parse_enabled'] ? 'true' : 'false');
        Setting::set('parse_interval_minutes', (string) $state['parse_interval_minutes']);

        Notification::make()->title('Settings saved')->success()->send();
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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save settings')
                ->action('save')
                ->color('primary'),
        ];
    }

    public static function getGeoOptions(): array
    {
        return collect(DashboardService::ingestGeos())
            ->mapWithKeys(fn($g) => [$g => $g])
            ->all();
    }
}
