<?php

namespace App\Filament\Pages;

use App\Services\ParserClient;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class ProxiesPage extends Page
{
    protected string $view = 'filament.pages.proxies';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGlobeAlt;

    protected static ?string $navigationLabel = 'Proxies';

    protected static ?string $title = 'Proxies';

    public array $proxies = [];
    public bool $parserDown = false;

    public function mount(): void
    {
        $this->loadProxies();
    }

    public function loadProxies(): void
    {
        try {
            $this->proxies   = app(ParserClient::class)->getProxies();
            $this->parserDown = false;
        } catch (\Throwable) {
            $this->proxies   = [];
            $this->parserDown = true;
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->loadProxies()),

            Action::make('add')
                ->label('Add proxy')
                ->icon('heroicon-o-plus')
                ->color('primary')
                ->modalHeading('Add proxy')
                ->modalDescription('Supported formats: http://user:pass@host:port  or  socks5://host:port')
                ->modalSubmitActionLabel('Add')
                ->form([
                    TextInput::make('url')
                        ->label('Proxy URL')
                        ->placeholder('http://user:pass@host:port')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    try {
                        app(ParserClient::class)->addProxy($data['url']);
                        $this->loadProxies();
                        $this->sighupReminder();
                        Notification::make()->title('Proxy added')->success()->send();
                    } catch (\Throwable $e) {
                        Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    public function toggleProxy(int $id, bool $currentEnabled): void
    {
        try {
            app(ParserClient::class)->toggleProxy($id, !$currentEnabled);
            $this->loadProxies();
            $this->sighupReminder();
            Notification::make()
                ->title('Proxy ' . (!$currentEnabled ? 'enabled' : 'disabled'))
                ->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }

    public function checkProxy(int $id): void
    {
        try {
            $result  = app(ParserClient::class)->checkProxy($id);
            $ok      = $result['ok'] ?? false;
            $latency = $result['latency_ms'] ?? '?';
            $ip      = $result['exit_ip'] ?? '?';
            $error   = $result['error'] ?? 'unknown';

            Notification::make()
                ->title($ok ? "OK — {$latency}ms, IP: {$ip}" : "Failed: {$error}")
                ->color($ok ? 'success' : 'danger')
                ->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }

    public function deleteProxy(int $id): void
    {
        try {
            app(ParserClient::class)->deleteProxy($id);
            $this->loadProxies();
            $this->sighupReminder();
            Notification::make()->title('Proxy deleted')->success()->send();
        } catch (\Throwable $e) {
            Notification::make()->title('Error: ' . $e->getMessage())->danger()->send();
        }
    }

    private function sighupReminder(): void
    {
        Notification::make()
            ->title('Reload parser worker to apply changes')
            ->body('docker compose kill -s HUP parser-worker-1')
            ->warning()
            ->persistent()
            ->send();
    }
}
