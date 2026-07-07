<?php

namespace App\Filament\Resources\Proxy;

use App\Filament\Resources\Proxy\Pages\ListProxies;
use App\Models\Proxy;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Http;

class ProxyResource extends Resource
{
    protected static ?string $model = Proxy::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Proxies';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('host')
                ->required()
                ->placeholder('192.168.1.1 or proxy.example.com'),

            TextInput::make('port')
                ->required()
                ->numeric()
                ->minValue(1)
                ->maxValue(65535),

            Select::make('protocol')
                ->options(['http' => 'HTTP', 'https' => 'HTTPS', 'socks5' => 'SOCKS5'])
                ->default('http')
                ->required(),

            TextInput::make('username')
                ->nullable(),

            TextInput::make('password')
                ->password()
                ->nullable(),

            Toggle::make('is_active')
                ->default(true),

            Textarea::make('notes')
                ->rows(2)
                ->nullable(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('host')
                    ->searchable()
                    ->description(fn(Proxy $r) => $r->protocol . '://' . $r->host . ':' . $r->port),

                TextColumn::make('port')
                    ->numeric(),

                TextColumn::make('protocol')
                    ->badge()
                    ->color(fn(string $state): string => match($state) {
                        'socks5' => 'warning',
                        'https'  => 'success',
                        default  => 'gray',
                    }),

                TextColumn::make('username')
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),

                TextColumn::make('last_status')
                    ->badge()
                    ->color(fn(?string $state): string => match($state) {
                        'ok'      => 'success',
                        'error'   => 'danger',
                        'timeout' => 'warning',
                        default   => 'gray',
                    })
                    ->placeholder('Not checked'),

                TextColumn::make('last_latency_ms')
                    ->label('Latency')
                    ->suffix(' ms')
                    ->placeholder('—'),

                TextColumn::make('last_checked_at')
                    ->dateTime('Y-m-d H:i')
                    ->placeholder('Never'),
            ])
            ->recordActions([
                Action::make('check')
                    ->label('Check')
                    ->icon('heroicon-o-signal')
                    ->color('info')
                    ->action(function (Proxy $record) {
                        static::checkProxy($record);
                    }),

                Action::make('toggle')
                    ->label(fn(Proxy $r) => $r->is_active ? 'Disable' : 'Enable')
                    ->icon(fn(Proxy $r) => $r->is_active ? 'heroicon-o-pause' : 'heroicon-o-play')
                    ->color(fn(Proxy $r) => $r->is_active ? 'warning' : 'success')
                    ->action(fn(Proxy $r) => $r->update(['is_active' => !$r->is_active])),

                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                BulkActionGroup::make([
                    BulkAction::make('enable_all')
                        ->label('Enable selected')
                        ->icon('heroicon-o-play')
                        ->color('success')
                        ->action(fn(Collection $records) => $records->each->update(['is_active' => true]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('disable_all')
                        ->label('Disable selected')
                        ->icon('heroicon-o-pause')
                        ->color('warning')
                        ->action(fn(Collection $records) => $records->each->update(['is_active' => false]))
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('check_all')
                        ->label('Check selected')
                        ->icon('heroicon-o-signal')
                        ->color('info')
                        ->action(fn(Collection $records) => $records->each(fn($r) => static::checkProxy($r)))
                        ->deselectRecordsAfterCompletion(),

                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    private static function checkProxy(Proxy $proxy): void
    {
        $proxyDsn = $proxy->protocol . '://'
            . ($proxy->username ? $proxy->username . ':' . $proxy->password . '@' : '')
            . $proxy->host . ':' . $proxy->port;

        $start = microtime(true);
        try {
            $response = Http::withOptions([
                'proxy'   => $proxyDsn,
                'timeout' => 10,
            ])->get('https://www.google.com');

            $latency = (int) ((microtime(true) - $start) * 1000);
            $status  = $response->successful() ? 'ok' : 'error';
        } catch (\Throwable) {
            $latency = (int) ((microtime(true) - $start) * 1000);
            $status  = $latency >= 10000 ? 'timeout' : 'error';
        }

        $proxy->update([
            'last_status'     => $status,
            'last_latency_ms' => $latency,
            'last_checked_at' => now(),
        ]);

        Notification::make()
            ->title('Proxy ' . $proxy->host . ': ' . strtoupper($status) . ' (' . $latency . 'ms)')
            ->color($status === 'ok' ? 'success' : 'danger')
            ->send();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProxies::route('/'),
        ];
    }
}
