<?php

namespace App\Filament\Pages;

use App\Jobs\IngestTrendingJob;
use App\Jobs\ScoreTopicsJob;
use App\Models\Setting;
use App\Services\DashboardService;
use Filament\Forms\Components\Placeholder;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
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

    public function mount(): void
    {
        $savedGeos = Setting::get('ingest_geos');
        $geos = $savedGeos ? (json_decode($savedGeos, true) ?: DashboardService::DEFAULT_GEOS) : DashboardService::DEFAULT_GEOS;

        $this->form->fill([
            'auto_parse_enabled'     => Setting::getBool('auto_parse_enabled', true),
            'parse_interval_minutes' => Setting::getInt('parse_interval_minutes', 30),
            'ingest_geos'            => $geos,
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

                Section::make('Geos to parse')
                    ->description('Select which countries to include in automatic trending ingestion. Uncheck all to use the full default list.')
                    ->schema([
                        CheckboxList::make('ingest_geos')
                            ->label('')
                            ->options(fn () => self::getGeoOptions())
                            ->columns(6)
                            ->searchable()
                            ->bulkToggleable(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $state = $this->form->getState();
        Setting::set('auto_parse_enabled', $state['auto_parse_enabled'] ? 'true' : 'false');
        Setting::set('parse_interval_minutes', (string) $state['parse_interval_minutes']);

        $geos = $state['ingest_geos'] ?? [];
        Setting::set('ingest_geos', json_encode(array_values($geos)));

        Notification::make()->title('Settings saved')->success()->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('parseNow')
                ->label('Parse now')
                ->icon('heroicon-o-play')
                ->color('success')
                ->modalHeading('One-time ingest')
                ->modalDescription('Select geos to parse. Leave all unchecked to run all 51 geos.')
                ->modalSubmitActionLabel('Dispatch jobs')
                ->form([
                    CheckboxList::make('geos')
                        ->label('Geos')
                        ->options(fn () => self::getGeoOptions())
                        ->columns(6)
                        ->searchable()
                        ->bulkToggleable(),
                ])
                ->action(function (array $data): void {
                    $geos = empty($data['geos'])
                        ? DashboardService::ingestGeos()
                        : $data['geos'];

                    foreach ($geos as $geo) {
                        IngestTrendingJob::dispatch($geo)->onQueue('default');
                    }

                    Notification::make()
                        ->title('Dispatched ' . count($geos) . ' ingest jobs')
                        ->success()
                        ->send();
                }),

            Action::make('recalculateScores')
                ->label('Recalculate scores')
                ->icon('heroicon-o-calculator')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Recalculate scores')
                ->modalDescription('Dispatch a scoring job for up to 100 unscored topics. Makes requests to the parser.')
                ->modalSubmitActionLabel('Dispatch')
                ->action(function (): void {
                    ScoreTopicsJob::dispatch(100)->onQueue('default');
                    Notification::make()->title('Scoring job dispatched')->success()->send();
                }),

            Action::make('save')
                ->label('Save settings')
                ->action('save')
                ->color('primary'),
        ];
    }

    public static function getGeoOptions(): array
    {
        return [
            'US' => 'United States',  'GB' => 'United Kingdom', 'CA' => 'Canada',
            'AU' => 'Australia',      'NZ' => 'New Zealand',    'IE' => 'Ireland',
            'DE' => 'Germany',        'FR' => 'France',         'IT' => 'Italy',
            'ES' => 'Spain',          'PT' => 'Portugal',       'NL' => 'Netherlands',
            'BE' => 'Belgium',        'CH' => 'Switzerland',    'AT' => 'Austria',
            'SE' => 'Sweden',         'NO' => 'Norway',         'DK' => 'Denmark',
            'FI' => 'Finland',        'PL' => 'Poland',         'CZ' => 'Czech Republic',
            'HU' => 'Hungary',        'RO' => 'Romania',        'GR' => 'Greece',
            'UA' => 'Ukraine',        'RU' => 'Russia',         'TR' => 'Turkey',
            'IL' => 'Israel',         'SA' => 'Saudi Arabia',   'AE' => 'UAE',
            'EG' => 'Egypt',          'ZA' => 'South Africa',   'NG' => 'Nigeria',
            'IN' => 'India',          'PK' => 'Pakistan',       'BD' => 'Bangladesh',
            'SG' => 'Singapore',      'MY' => 'Malaysia',       'PH' => 'Philippines',
            'ID' => 'Indonesia',      'TH' => 'Thailand',       'VN' => 'Vietnam',
            'HK' => 'Hong Kong',      'TW' => 'Taiwan',         'JP' => 'Japan',
            'KR' => 'South Korea',    'BR' => 'Brazil',         'MX' => 'Mexico',
            'AR' => 'Argentina',      'CL' => 'Chile',          'CO' => 'Colombia',
        ];
    }
}
