<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming\Pages;

use App\Enums\FilamentPanel;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use He4rt\Identity\ExternalIdentity\Actions\DisconnectExternalIdentity;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationTwitch\OAuth\TwitchScopes;
use He4rt\PanelApp\Clusters\Streaming\StreamingCluster;
use He4rt\PanelApp\Clusters\Streaming\StreamingPreviewData;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\StreamEvent\Actions\TriggerTestAlert;
use Livewire\Attributes\Computed;

/**
 * @property-read ExternalIdentity|null $twitchConnection
 * @property-read array<int, string> $missingTwitchScopes
 */
class StreamDashboardPage extends Page
{
    protected static ?string $cluster = StreamingCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPresentationChartLine;

    protected static ?string $navigationLabel = 'Painel';

    protected static ?string $title = 'Painel';

    protected static ?string $slug = 'painel';

    protected static ?int $navigationSort = 1;

    protected string $view = 'panel-app::pages.streaming.dashboard';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('use-streamer-tools') ?? false;
    }

    public function mount(EnsureStreamer $ensureStreamer): void
    {
        $ensureStreamer->handle($this->currentUser());
    }

    #[Computed]
    public function twitchConnection(): ?ExternalIdentity
    {
        return $this->currentUser()
            ->providers()
            ->where('provider', IdentityProvider::Twitch)
            ->activelyConnected()
            ->latest('connected_at')
            ->first();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function missingTwitchScopes(): array
    {
        return $this->twitchConnection?->missingScopes(TwitchScopes::requestedFor(FilamentPanel::App->value, $this->currentUser())) ?? [];
    }

    public function connectTwitchAction(): Action
    {
        return Action::make('connectTwitch')
            ->label('Conectar Twitch')
            ->icon(IdentityProvider::Twitch->getIcon())
            ->url($this->twitchAuthorizationUrl())
            ->visible(fn (): bool => !$this->twitchConnection instanceof ExternalIdentity);
    }

    public function reauthorizeTwitchAction(): Action
    {
        return Action::make('reauthorizeTwitch')
            ->label('Reautorizar')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->size('sm')
            ->url($this->twitchAuthorizationUrl())
            ->visible(fn (): bool => $this->missingTwitchScopes !== []);
    }

    public function disconnectTwitchAction(): Action
    {
        return Action::make('disconnectTwitch')
            ->label('Desconectar')
            ->color('gray')
            ->size('sm')
            ->requiresConfirmation()
            ->modalIcon(Heroicon::OutlinedLinkSlash)
            ->modalHeading('Desconectar a Twitch?')
            ->modalDescription('Os alertas e as overlays param de receber eventos do seu canal até você conectar de novo.')
            ->modalSubmitActionLabel('Desconectar')
            ->visible(fn (): bool => $this->twitchConnection instanceof ExternalIdentity)
            ->action(function (DisconnectExternalIdentity $disconnectIdentity): void {
                if ($this->twitchConnection instanceof ExternalIdentity) {
                    $disconnectIdentity->handle($this->twitchConnection);
                }

                unset($this->twitchConnection, $this->missingTwitchScopes);

                Notification::make()
                    ->title('Twitch desconectada')
                    ->success()
                    ->send();
            });
    }

    public function sendTestAlert(TriggerTestAlert $triggerTestAlert, string $type): void
    {
        $alertType = StreamEventType::tryFrom($type);

        if ($alertType === null) {
            return;
        }

        $triggerTestAlert->handle($this->currentStreamer(), $alertType);

        Notification::make()
            ->title(sprintf('Alerta de %s enviado', $alertType->getLabel()))
            ->body('Confira na sua overlay.')
            ->success()
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'alertTypes' => StreamEventType::cases(),
            'stats' => StreamingPreviewData::stats(),
            'recentActivity' => StreamingPreviewData::recentActivity(),
        ];
    }

    private function twitchAuthorizationUrl(): string
    {
        return route('oauth.redirect', [
            'panel' => FilamentPanel::App->value,
            'provider' => IdentityProvider::Twitch->value,
        ]);
    }

    private function currentUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function currentStreamer(): Streamer
    {
        return Streamer::query()->whereBelongsTo($this->currentUser())->sole();
    }
}
