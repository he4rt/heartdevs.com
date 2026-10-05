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
use He4rt\PanelApp\Clusters\Streaming\SessionComparison;
use He4rt\PanelApp\Clusters\Streaming\StreamDuration;
use He4rt\PanelApp\Clusters\Streaming\StreamEventSummary;
use He4rt\PanelApp\Clusters\Streaming\StreamingCluster;
use He4rt\PanelApp\Clusters\Streaming\StreamingHealthBadge;
use He4rt\Streaming\Enums\ChatReader;
use He4rt\Streaming\Health\Checks\CheckOverlayConnections;
use He4rt\Streaming\Health\Contracts\OverlayConnections;
use He4rt\Streaming\Health\HealthCheck;
use He4rt\Streaming\Session\Data\SessionTotals;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Session\Queries\StreamSessionChat;
use He4rt\Streaming\Session\Queries\StreamSessionHistory;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Actions\UpdateStreamerSource;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Actions\ReplayStreamEventAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;

/**
 * @property-read ExternalIdentity|null $twitchConnection
 * @property-read StreamerSource|null $twitchSource
 * @property-read Collection<int, StreamerSource> $sources
 * @property-read array<int, string> $missingTwitchScopes
 * @property-read array<int, HealthCheck> $healthChecks
 * @property-read StreamSession|null $liveSession
 */
class StreamDashboardPage extends Page
{
    private const int RECENT_ACTIVITY_LIMIT = 10;

    private const string WITHOUT_CHAT = 'none';

    private const int CHAT_PACE_MINUTES = 5;

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

    #[Computed]
    public function liveSession(): ?StreamSession
    {
        return resolve(StreamSessionHistory::class)->live($this->currentStreamer());
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
            ->label('Desconectar a Twitch')
            ->icon(Heroicon::OutlinedLinkSlash)
            ->color('danger')
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
            ->label(fn (array $arguments): string => $this->ownedSource($arguments)?->enabled === true ? 'Desligar fonte' : 'Ligar fonte')
            ->icon(fn (array $arguments): Heroicon => $this->ownedSource($arguments)?->enabled === true ? Heroicon::OutlinedPauseCircle : Heroicon::OutlinedPlayCircle)
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

    public function replayAlertAction(): Action
    {
        return Action::make('replayAlert')
            ->label('Repetir alerta')
            ->tooltip('Repetir o alerta na overlay')
            ->icon(Heroicon::OutlinedArrowPathRoundedSquare)
            ->iconButton()
            ->color('gray')
            ->size('sm')
            ->action(function (array $arguments, ReplayStreamEventAlert $replayAlert): void {
                $eventId = $arguments['event'] ?? null;

                if (!is_string($eventId)) {
                    return;
                }

                $event = $replayAlert->handle($this->currentStreamer(), $eventId);

                Notification::make()
                    ->title(sprintf('Alerta de %s repetido', $event->type->getLabel()))
                    ->body('Confira na sua overlay.')
                    ->success()
                    ->send();
            });
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $streamer = $this->currentStreamer();
        $live = $this->liveSummary($streamer);
        $mainSource = $this->twitchSource;

        return [
            'live' => $live,
            'lastLive' => $live === null ? $this->lastLiveSummary($streamer) : null,
            'otherSources' => $this->sources->reject(fn (StreamerSource $source): bool => $source->is($mainSource)),
            'recentActivity' => $streamer->events()
                ->latest('occurred_at')
                ->limit(self::RECENT_ACTIVITY_LIMIT)
                ->get()
                ->map(StreamEventSummary::of(...))
                ->all(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function liveSummary(Streamer $streamer): ?array
    {
        $session = $this->liveSession;

        if (!$session instanceof StreamSession) {
            return null;
        }

        $totals = SessionTotals::of($session);

        return [
            'session' => $session,
            'duration' => StreamDuration::between($session->started_at, now()),
            'rows' => SessionComparison::rows($totals),
            'messagesPerMinute' => resolve(StreamSessionChat::class)->messagesPerMinute($session, self::CHAT_PACE_MINUTES),
            'chatters' => $totals->chatters,
            'openOverlays' => resolve(OverlayConnections::class)->count($streamer),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function lastLiveSummary(Streamer $streamer): ?array
    {
        $history = resolve(StreamSessionHistory::class);
        $session = $history->lastEnded($streamer);

        if (!$session instanceof StreamSession) {
            return null;
        }

        $baseline = $history->baselineFor($session);
        $totals = SessionTotals::of($session);

        return [
            'session' => $session,
            'duration' => StreamDuration::between($session->started_at, $session->ended_at ?? now()),
            'hasNoData' => $totals->hasNoData(),
            'rows' => SessionComparison::rows($totals, $baseline instanceof StreamSession ? SessionTotals::of($baseline) : null),
            'baseline' => $baseline,
        ];
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
