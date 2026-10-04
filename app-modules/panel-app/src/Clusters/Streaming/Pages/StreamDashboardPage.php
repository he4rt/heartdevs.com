<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming\Pages;

use App\Enums\FilamentPanel;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use He4rt\Identity\ExternalIdentity\Actions\DisconnectExternalIdentity;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationTwitch\Actions\RepairStreamerTwitchSubscriptions;
use He4rt\IntegrationTwitch\Exceptions\TwitchUnreachable;
use He4rt\IntegrationTwitch\Health\TwitchHealthReport;
use He4rt\IntegrationTwitch\OAuth\TwitchBotTokenService;
use He4rt\IntegrationTwitch\OAuth\TwitchScopes;
use He4rt\IntegrationTwitch\OAuth\TwitchStreamerFeature;
use He4rt\IntegrationTwitch\OAuth\TwitchUserAuthorization;
use He4rt\PanelApp\Clusters\Streaming\StreamingCluster;
use He4rt\PanelApp\Clusters\Streaming\StreamingHealthBadge;
use He4rt\Streaming\Enums\ChatReader;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Health\Checks\CheckOverlayConnections;
use He4rt\Streaming\Health\HealthCheck;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Actions\UpdateStreamerSource;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Actions\TriggerTestAlert;
use He4rt\Streaming\StreamEvent\Data\CheerDetails;
use He4rt\Streaming\StreamEvent\Data\GiftSubDetails;
use He4rt\Streaming\StreamEvent\Data\RaidDetails;
use He4rt\Streaming\StreamEvent\Data\SubDetails;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;
use He4rt\Streaming\StreamEvent\Queries\StreamerStats;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;

/**
 * @property-read ExternalIdentity|null $twitchConnection
 * @property-read StreamerSource|null $twitchSource
 * @property-read Collection<int, StreamerSource> $sources
 * @property-read array<int, string> $missingTwitchScopes
 * @property-read array<int, HealthCheck> $healthChecks
 */
class StreamDashboardPage extends Page
{
    private const int STATS_WINDOW_DAYS = 30;

    private const int RECENT_ACTIVITY_LIMIT = 10;

    private const string WITHOUT_CHAT = 'none';

    public bool $healthRequested = false;

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

    public static function getNavigationBadge(): ?string
    {
        return StreamingHealthBadge::label();
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return StreamingHealthBadge::TOOLTIP;
    }

    public function mount(EnsureStreamer $ensureStreamer): void
    {
        $ensureStreamer->handle($this->currentUser());
    }

    public function loadHealth(): void
    {
        $this->healthRequested = true;
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

    #[Computed]
    public function twitchSource(): ?StreamerSource
    {
        $connection = $this->twitchConnection;

        if (!$connection instanceof ExternalIdentity) {
            return null;
        }

        return $this->currentStreamer()->sources()->where('external_identity_id', $connection->getKey())->first();
    }

    /**
     * @return Collection<int, StreamerSource>
     */
    #[Computed]
    public function sources(): Collection
    {
        return $this->currentStreamer()
            ->sources()
            ->whereHas('identity', fn (Builder $identity): Builder => $identity->activelyConnected())
            ->with('identity')
            ->oldest()
            ->get();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function missingTwitchScopes(): array
    {
        return $this->twitchConnection?->missingScopes($this->requiredTwitchScopes($this->twitchSource?->chat_reader)) ?? [];
    }

    /**
     * @return array<int, HealthCheck>
     */
    #[Computed]
    public function healthChecks(): array
    {
        $source = $this->twitchSource;

        if (!$this->healthRequested || !$source instanceof StreamerSource) {
            return [];
        }

        return [
            ...resolve(TwitchHealthReport::class)->for($source),
            resolve(CheckOverlayConnections::class)->handle($this->currentStreamer()),
        ];
    }

    public function connectTwitchAction(): Action
    {
        return Action::make('connectTwitch')
            ->label('Conectar Twitch')
            ->icon(IdentityProvider::Twitch->getIcon())
            ->modalHeading('Conectar a Twitch')
            ->modalDescription('Escolha o que a He4rt usa do seu canal. A Twitch pede só as permissões disso.')
            ->modalSubmitActionLabel('Conectar')
            ->schema([
                Toggle::make('alerts')
                    ->label(TwitchStreamerFeature::Alerts->getLabel())
                    ->helperText(TwitchStreamerFeature::Alerts->getDescription())
                    ->default(state: true)
                    ->disabled(),
                Toggle::make('chat')
                    ->label('Chat na overlay')
                    ->live(),
                Radio::make('chat_reader')
                    ->label('Quem lê o chat')
                    ->options($this->chatReaderOptions())
                    ->descriptions($this->chatReaderDescriptions())
                    ->default(ChatReader::OwnAccount->value)
                    ->required()
                    ->visible(fn (Get $get): bool => (bool) $get('chat')),
            ])
            ->visible(fn (): bool => !$this->twitchConnection instanceof ExternalIdentity)
            ->action(function (array $data): void {
                $wantsChat = (bool) ($data['chat'] ?? false);

                $this->redirect($this->twitchAuthorizationUrl($wantsChat ? $this->chatReaderFrom($data) : null));
            });
    }

    public function reauthorizeTwitchAction(): Action
    {
        return Action::make('reauthorizeTwitch')
            ->label('Reautorizar')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('warning')
            ->size('sm')
            ->url(fn (): string => $this->twitchAuthorizationUrl($this->twitchSource?->chat_reader))
            ->visible(fn (): bool => $this->missingTwitchScopes !== []);
    }

    public function reconnectTwitchAction(): Action
    {
        return Action::make('reconnectTwitch')
            ->label('Reconectar Twitch')
            ->icon(Heroicon::OutlinedArrowPath)
            ->color('danger')
            ->size('sm')
            ->url(fn (): string => $this->twitchAuthorizationUrl($this->twitchSource?->chat_reader));
    }

    public function repairSubscriptionsAction(): Action
    {
        return Action::make('repairSubscriptions')
            ->label('Reparar inscrições')
            ->icon(Heroicon::OutlinedWrenchScrewdriver)
            ->color('warning')
            ->size('sm')
            ->action(function (RepairStreamerTwitchSubscriptions $repairSubscriptions): void {
                $source = $this->twitchSource;

                if (!$source instanceof StreamerSource) {
                    return;
                }

                try {
                    $repairSubscriptions->handle($source);
                } catch (TwitchUnreachable) {
                    Notification::make()
                        ->title('A Twitch não respondeu')
                        ->body('Nada mudou. Tente de novo em instantes.')
                        ->danger()
                        ->send();

                    return;
                }

                unset($this->healthChecks);

                Notification::make()
                    ->title('Inscrições reparadas')
                    ->body('A Twitch confirma as novas em alguns segundos.')
                    ->success()
                    ->send();
            });
    }

    public function recheckHealthAction(): Action
    {
        return Action::make('recheckHealth')
            ->label('Verificar de novo')
            ->icon(Heroicon::OutlinedArrowPath)
            ->link()
            ->color('gray')
            ->size('sm')
            ->action(function (TwitchUserAuthorization $authorization): void {
                if ($this->twitchConnection instanceof ExternalIdentity) {
                    $authorization->forget($this->twitchConnection);
                }

                unset($this->healthChecks);
            });
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

                unset($this->twitchConnection, $this->twitchSource, $this->sources, $this->missingTwitchScopes, $this->healthChecks);

                Notification::make()
                    ->title('Twitch desconectada')
                    ->success()
                    ->send();
            });
    }

    public function toggleSourceAction(): Action
    {
        return Action::make('toggleSource')
            ->label(fn (array $arguments): string => $this->ownedSource($arguments)?->enabled === true ? 'Desligar' : 'Ligar')
            ->color('gray')
            ->size('sm')
            ->action(function (array $arguments, UpdateStreamerSource $updateSource): void {
                $source = $this->ownedSource($arguments);

                if (!$source instanceof StreamerSource) {
                    return;
                }

                $source = $updateSource->handle($source, !$source->enabled, $source->chat_reader);

                unset($this->twitchSource, $this->sources);

                Notification::make()
                    ->title($source->enabled ? 'Fonte ligada' : 'Fonte desligada')
                    ->body($source->enabled ? null : 'Os eventos continuam gravados, mas não chegam à overlay.')
                    ->success()
                    ->send();
            });
    }

    public function chatReaderAction(): Action
    {
        return Action::make('chatReader')
            ->label('Alterar chat')
            ->icon(Heroicon::OutlinedChatBubbleLeftRight)
            ->color('gray')
            ->size('sm')
            ->modalHeading('Quem lê o chat da live?')
            ->modalDescription('O chat aparece na overlay de coworking.')
            ->modalSubmitActionLabel('Salvar')
            ->fillForm(fn (array $arguments): array => [
                'chat_reader' => $this->ownedSource($arguments)?->chat_reader->value ?? self::WITHOUT_CHAT,
            ])
            ->schema([
                Radio::make('chat_reader')
                    ->hiddenLabel()
                    ->options([self::WITHOUT_CHAT => 'Sem chat na overlay', ...$this->chatReaderOptions()])
                    ->descriptions($this->chatReaderDescriptions())
                    ->required(),
            ])
            ->action(function (array $arguments, array $data, UpdateStreamerSource $updateSource): void {
                $source = $this->ownedSource($arguments);

                if (!$source instanceof StreamerSource) {
                    return;
                }

                $chatReader = $this->chatReaderFrom($data);
                $updateSource->handle($source, $source->enabled, $chatReader);

                unset($this->twitchSource, $this->sources, $this->missingTwitchScopes);

                $needsNewScopes = $source->identity->missingScopes($this->requiredTwitchScopes($chatReader)) !== [];

                if ($needsNewScopes) {
                    $this->redirect($this->twitchAuthorizationUrl($chatReader));

                    return;
                }

                Notification::make()
                    ->title('Leitor do chat atualizado')
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
        $streamer = $this->currentStreamer();
        $totals = resolve(StreamerStats::class)->lastDays($streamer, self::STATS_WINDOW_DAYS);
        $statTypes = [StreamEventType::Follow, StreamEventType::Sub, StreamEventType::Cheer, StreamEventType::Raid];

        return [
            'alertTypes' => StreamEventType::cases(),
            'stats' => array_map(fn (StreamEventType $type): array => [
                'type' => $type,
                'value' => number_format($totals[$type->value], thousands_separator: '.'),
            ], $statTypes),
            'recentActivity' => $streamer->events()
                ->latest('occurred_at')
                ->limit(self::RECENT_ACTIVITY_LIMIT)
                ->get()
                ->map(fn (StreamEvent $event): array => [
                    'id' => $event->id,
                    'type' => $event->type,
                    'username' => $event->actor()?->displayName,
                    'detail' => $this->activityDetail($event),
                    'at' => $event->occurred_at,
                ])
                ->all(),
        ];
    }

    private function activityDetail(StreamEvent $event): string
    {
        $details = $event->details;

        return match (true) {
            $details instanceof SubDetails => sprintf('%s · %d %s', $details->tier->getLabel(), $details->months, $details->months === 1 ? 'mês' : 'meses'),
            $details instanceof GiftSubDetails => sprintf('%d %s', $details->total, $details->total === 1 ? 'sub' : 'subs'),
            $details instanceof CheerDetails => sprintf('%s bits', number_format($details->bits, thousands_separator: '.')),
            $details instanceof RaidDetails => sprintf('+%d viewers', $details->viewers),
            default => '',
        };
    }

    /**
     * @return array<int, string>
     */
    private function requiredTwitchScopes(?ChatReader $chatReader): array
    {
        return TwitchScopes::requestedFor(FilamentPanel::App->value, $this->currentUser(), TwitchStreamerFeature::neededFor($chatReader));
    }

    private function twitchAuthorizationUrl(?ChatReader $chatReader): string
    {
        return route('oauth.redirect', [
            'panel' => FilamentPanel::App->value,
            'provider' => IdentityProvider::Twitch->value,
            'features' => array_map(fn (TwitchStreamerFeature $feature): string => $feature->value, TwitchStreamerFeature::neededFor($chatReader)),
        ]);
    }

    /**
     * @return array<int, ChatReader>
     */
    private function availableChatReaders(): array
    {
        return TwitchBotTokenService::isConfigured() ? ChatReader::cases() : [ChatReader::OwnAccount];
    }

    /**
     * @return array<string, string>
     */
    private function chatReaderOptions(): array
    {
        $options = [];

        foreach ($this->availableChatReaders() as $chatReader) {
            $options[$chatReader->value] = $chatReader->getLabel();
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    private function chatReaderDescriptions(): array
    {
        $descriptions = [];

        foreach ($this->availableChatReaders() as $chatReader) {
            $descriptions[$chatReader->value] = $chatReader->getDescription();
        }

        return $descriptions;
    }

    /**
     * @param  array<array-key, mixed>  $data
     */
    private function chatReaderFrom(array $data): ?ChatReader
    {
        $chatReader = $data['chat_reader'] ?? null;

        return is_string($chatReader) ? ChatReader::tryFrom($chatReader) : null;
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     */
    private function ownedSource(array $arguments): ?StreamerSource
    {
        $sourceId = $arguments['source'] ?? null;

        if (!is_string($sourceId)) {
            return null;
        }

        return $this->currentStreamer()->sources()->whereKey($sourceId)->first();
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
