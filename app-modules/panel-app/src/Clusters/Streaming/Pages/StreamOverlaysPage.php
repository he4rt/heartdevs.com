<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming\Pages;

use BackedEnum;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationSlido\Data\SlidoEventHash;
use He4rt\PanelApp\Clusters\Streaming\StreamingCluster;
use He4rt\Streaming\Enums\ChatAlignment;
use He4rt\Streaming\Enums\ChatDirection;
use He4rt\Streaming\Enums\ChatStyle;
use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Enums\VoiceLayout;
use He4rt\Streaming\Health\Contracts\OverlayConnections;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Actions\RegenerateOverlayToken;
use He4rt\Streaming\Streamer\Actions\UpdateStreamerSettings;
use He4rt\Streaming\Streamer\Data\AlertSettings;
use He4rt\Streaming\Streamer\Data\ChatSettings;
use He4rt\Streaming\Streamer\Data\PubSettings;
use He4rt\Streaming\Streamer\Data\StartingSoonSettings;
use He4rt\Streaming\Streamer\Data\VoiceSettings;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Actions\TriggerTestAlert;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Renderless;

/**
 * @property-read Collection<int, StreamerSource> $overlaySources
 */
class StreamOverlaysPage extends Page
{
    private const int SCENE_WIDTH = 1_920;

    private const int CHAT_PREVIEW_WIDTH = 960;

    private const string WALL_CLOCK_PATTERN = '/^([01]\d|2[0-3]):[0-5]\d$/';

    #[Locked]
    public string $overlayToken = '';

    protected static ?string $cluster = StreamingCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Overlays';

    protected static ?string $title = 'Overlays';

    protected static ?string $slug = 'overlays';

    protected static ?int $navigationSort = 2;

    protected string $view = 'panel-app::pages.streaming.overlays';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('use-streamer-tools') ?? false;
    }

    public function mount(EnsureStreamer $ensureStreamer): void
    {
        $this->overlayToken = $ensureStreamer->handle($this->currentUser())->overlay_token;
    }

    public function startingSoonSettingsAction(): Action
    {
        return Action::make('startingSoonSettings')
            ->label('Configurar')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->color('gray')
            ->size('sm')
            ->modalHeading(OverlayScene::StartingSoon->getLabel())
            ->modalDescription('O título e o horário aparecem na cena de abertura. A overlay aberta no OBS muda na hora.')
            ->modalSubmitActionLabel('Salvar')
            ->fillForm(fn (): array => $this->currentStreamer()->settings->startingSoon->toArray())
            ->schema([
                TextInput::make('title')
                    ->label('Título da live')
                    ->placeholder('Live de PHP')
                    ->maxLength(80),
                TextInput::make('starts_at')
                    ->label('Começa às')
                    ->type('time')
                    ->regex(self::WALL_CLOCK_PATTERN),
            ])
            ->action(function (array $data, UpdateStreamerSettings $updateSettings): void {
                $streamer = $this->currentStreamer();

                $updateSettings->handle($streamer, $streamer->settings->withScene(StartingSoonSettings::fromArray($data)));

                Notification::make()
                    ->title('Cena de abertura atualizada')
                    ->success()
                    ->send();
            });
    }

    public function voiceSettingsAction(): Action
    {
        $layoutLabels = [];
        $layoutDescriptions = [];

        foreach (VoiceLayout::cases() as $layout) {
            $layoutLabels[$layout->value] = $layout->getLabel();
            $layoutDescriptions[$layout->value] = $layout->getDescription();
        }

        return Action::make('voiceSettings')
            ->label('Configurar')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->color('gray')
            ->size('sm')
            ->modalHeading(OverlayScene::Voice->getLabel())
            ->modalSubmitActionLabel('Salvar')
            ->fillForm(fn (): array => $this->currentStreamer()->settings->voice->toArray())
            ->schema([
                Radio::make('layout')
                    ->label('Disposição')
                    ->options($layoutLabels)
                    ->descriptions($layoutDescriptions)
                    ->required(),
            ])
            ->action(function (array $data, UpdateStreamerSettings $updateSettings): void {
                $streamer = $this->currentStreamer();

                $updateSettings->handle($streamer, $streamer->settings->withScene(VoiceSettings::fromArray($data)));

                Notification::make()
                    ->title('Sala de voz atualizada')
                    ->success()
                    ->send();
            });
    }

    public function chatSettingsAction(): Action
    {
        $styleDescriptions = [];

        foreach (ChatStyle::cases() as $style) {
            $styleDescriptions[$style->value] = $style->getDescription();
        }

        return Action::make('chatSettings')
            ->label('Configurar')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->color('gray')
            ->size('sm')
            ->modalHeading(OverlayScene::Chat->getLabel())
            ->modalDescription('A overlay aberta no OBS muda na hora.')
            ->modalSubmitActionLabel('Salvar')
            ->fillForm(fn (): array => $this->currentStreamer()->settings->chat->toArray())
            ->schema([
                Radio::make('style')
                    ->label('Estilo')
                    ->options($this->labelsOf(ChatStyle::cases()))
                    ->descriptions($styleDescriptions)
                    ->columns(2)
                    ->required(),
                Grid::make(3)->schema([
                    TextInput::make('fade_after_seconds')
                        ->label('Sumir após')
                        ->helperText('0 = nunca some')
                        ->integer()
                        ->minValue(ChatSettings::NEVER_FADE)
                        ->maxValue(ChatSettings::MAX_FADE_SECONDS)
                        ->suffix('s')
                        ->required(),
                    TextInput::make('font_size')
                        ->label('Fonte')
                        ->integer()
                        ->minValue(ChatSettings::MIN_FONT_SIZE)
                        ->maxValue(ChatSettings::MAX_FONT_SIZE)
                        ->suffix('px')
                        ->required(),
                    TextInput::make('width')
                        ->label('Largura')
                        ->integer()
                        ->minValue(ChatSettings::MIN_WIDTH)
                        ->maxValue(ChatSettings::MAX_WIDTH)
                        ->suffix('px')
                        ->required(),
                ]),
                ToggleButtons::make('direction')
                    ->label('Direção')
                    ->options($this->labelsOf(ChatDirection::cases()))
                    ->inline()
                    ->required(),
                ToggleButtons::make('alignment')
                    ->label('Alinhamento')
                    ->options($this->labelsOf(ChatAlignment::cases()))
                    ->inline()
                    ->required(),
            ])
            ->action(function (array $data, UpdateStreamerSettings $updateSettings): void {
                $streamer = $this->currentStreamer();

                $updateSettings->handle($streamer, $streamer->settings->withScene(ChatSettings::fromArray($data)));

                Notification::make()
                    ->title('Chat atualizado')
                    ->success()
                    ->send();
            });
    }

    public function pubSettingsAction(): Action
    {
        return Action::make('pubSettings')
            ->label('Configurar')
            ->icon(Heroicon::OutlinedAdjustmentsHorizontal)
            ->color('gray')
            ->size('sm')
            ->modalHeading(OverlayScene::Pub->getLabel())
            ->modalDescription('Cole o link do evento do Slido da edição. A overlay mostra as perguntas e o QR Code desse evento. Deixe vazio para tirar o Slido da overlay.')
            ->modalSubmitActionLabel('Salvar')
            ->fillForm(fn (): array => ['slido_event' => $this->currentStreamer()->settings->pub->slidoEventHash])
            ->schema([
                TextInput::make('slido_event')
                    ->label('Evento do Slido')
                    ->placeholder('https://app.sli.do/event/jC9L7Lu93Vw3ssNhB92Pvb')
                    ->helperText('Link do participante, do wall ou só o código do evento.')
                    ->rule(fn (): Closure => function (string $attribute, mixed $value, Closure $fail): void {
                        $isFilledButUnknown = is_string($value) && $value !== '' && SlidoEventHash::fromInput($value) === null;

                        if ($isFilledButUnknown) {
                            $fail('Não reconheci esse link do Slido.');
                        }
                    }),
            ])
            ->action(function (array $data, UpdateStreamerSettings $updateSettings): void {
                $streamer = $this->currentStreamer();
                $slidoEvent = $data['slido_event'] ?? null;
                $eventHash = is_string($slidoEvent) && $slidoEvent !== '' ? SlidoEventHash::fromInput($slidoEvent) : null;

                $updateSettings->handle($streamer, $streamer->settings->withScene(new PubSettings($eventHash)));

                Notification::make()
                    ->title('He4rt Pub atualizada')
                    ->body($eventHash === null ? 'A overlay deixa de mostrar o Slido.' : 'A overlay passa a mostrar as perguntas desse evento em alguns segundos.')
                    ->success()
                    ->send();
            });
    }

    public function sceneSourcesAction(): Action
    {
        return Action::make('sceneSources')
            ->label(fn (array $arguments): string => $this->sceneSourcesLabel($this->sceneOf($arguments)))
            ->icon(Heroicon::OutlinedSignal)
            ->color('gray')
            ->size('sm')
            ->modalHeading(fn (array $arguments): string => sprintf('Fontes: %s', $this->sceneOf($arguments)?->getLabel() ?? 'cena'))
            ->modalDescription('De quais canais esta cena recebe chat e alertas. Sem nenhum marcado, ela usa todas as fontes ligadas. A overlay aberta no OBS recarrega sozinha.')
            ->modalSubmitActionLabel('Salvar')
            ->fillForm(fn (array $arguments): array => [
                'sources' => $this->sceneOf($arguments) instanceof OverlayScene
                    ? $this->currentStreamer()->settings->sceneSources->chosenFor($this->sceneOf($arguments))
                    : [],
            ])
            ->schema([
                CheckboxList::make('sources')
                    ->hiddenLabel()
                    ->options(fn (): array => $this->overlaySources->mapWithKeys(fn (StreamerSource $source): array => [$source->id => $this->sourceHandle($source)])->all())
                    ->descriptions(fn (): array => $this->overlaySources->mapWithKeys(fn (StreamerSource $source): array => [
                        $source->id => sprintf('%s · %s', $source->identity->provider->getLabel(), $source->enabled ? 'ligada' : 'desligada no Painel'),
                    ])->all()),
            ])
            ->visible(fn (): bool => $this->overlaySources->count() > 1)
            ->action(function (array $arguments, array $data, UpdateStreamerSettings $updateSettings): void {
                $scene = $this->sceneOf($arguments);

                if (!$scene instanceof OverlayScene) {
                    return;
                }

                $submitted = is_array($data['sources'] ?? null) ? $data['sources'] : [];
                $ownedSourceIds = array_values(array_intersect($submitted, $this->overlaySourceIds()));
                $streamer = $this->currentStreamer();

                $updateSettings->handle($streamer, $streamer->settings->withSceneSources($scene, $ownedSourceIds));

                Notification::make()
                    ->title('Fontes da cena atualizadas')
                    ->body($ownedSourceIds === [] ? 'A cena volta a usar todas as fontes ligadas.' : null)
                    ->success()
                    ->send();
            });
    }

    /**
     * @return Collection<int, StreamerSource>
     */
    #[Computed]
    public function overlaySources(): Collection
    {
        return $this->currentStreamer()->sources()->with('identity')->oldest()->get();
    }

    public function testAlertAction(): Action
    {
        return Action::make('testAlert')
            ->label(function (array $arguments): string {
                $alertType = $this->alertTypeOf($arguments);

                return $alertType instanceof StreamEventType ? sprintf('%s %s', $alertType->getEmoji(), $alertType->getLabel()) : '';
            })
            ->requiresConfirmation(fn (): bool => $this->currentStreamer()->isLive())
            ->modalIcon(Heroicon::OutlinedSignal)
            ->modalHeading('Você está ao vivo')
            ->modalDescription('O alerta de teste vai aparecer na live para quem está assistindo.')
            ->modalSubmitActionLabel('Mandar mesmo assim')
            ->action(function (array $arguments, TriggerTestAlert $triggerTestAlert): void {
                $alertType = $this->alertTypeOf($arguments);

                if (!$alertType instanceof StreamEventType) {
                    return;
                }

                $triggerTestAlert->handle($this->currentStreamer(), $alertType);

                Notification::make()
                    ->title(sprintf('Alerta de %s enviado', $alertType->getLabel()))
                    ->body('Confira na sua overlay.')
                    ->success()
                    ->send();
            });
    }

    #[Renderless]
    public function countOpenOverlays(OverlayConnections $connections): ?int
    {
        return $connections->count($this->currentStreamer());
    }

    /**
     * @return Action[]
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('alertSettings')
                ->label('Alertas')
                ->icon(Heroicon::OutlinedBellAlert)
                ->color('gray')
                ->modalHeading('Alertas na overlay')
                ->modalDescription('Os eventos de um alerta desligado continuam gravados, mas não aparecem na overlay.')
                ->modalSubmitActionLabel('Salvar')
                ->fillForm(fn (): array => $this->currentStreamer()->settings->alerts->toArray())
                ->schema(array_map(
                    fn (StreamEventType $type): Toggle => Toggle::make($type->value)->label($type->getLabel()),
                    StreamEventType::cases(),
                ))
                ->action(function (array $data, UpdateStreamerSettings $updateSettings): void {
                    $streamer = $this->currentStreamer();

                    $updateSettings->handle($streamer, $streamer->settings->withAlerts(AlertSettings::fromArray($data)));

                    Notification::make()
                        ->title('Alertas atualizados')
                        ->success()
                        ->send();
                }),
            Action::make('regenerateToken')
                ->label('Gerar novos links')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Gerar novos links?')
                ->modalDescription('Os links atuais param de funcionar. Você vai precisar colar os novos no OBS.')
                ->modalSubmitActionLabel('Gerar novos links')
                ->action(function (RegenerateOverlayToken $regenerateOverlayToken): void {
                    $this->overlayToken = $regenerateOverlayToken->handle($this->currentStreamer());

                    Notification::make()
                        ->title('Links das overlays atualizados')
                        ->success()
                        ->send();
                }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $streamer = $this->currentStreamer();
        $settingsVersion = mb_substr(md5(json_encode($streamer->settings->toArray(), JSON_THROW_ON_ERROR)), 0, 8);

        return [
            'openOverlays' => resolve(OverlayConnections::class)->count($streamer),
            'alertTypes' => StreamEventType::cases(),
            'scenes' => array_map(fn (OverlayScene $scene): array => [
                'scene' => $scene,
                'url' => $this->sceneUrl($scene, $this->overlayToken),
                'previewUrl' => $this->sceneUrl($scene, $this->overlayToken).'?'.http_build_query(['demo' => 1, 'v' => $settingsVersion]),
                'maskedUrl' => $this->sceneUrl($scene, str_repeat('•', 12)),
                'settingsAction' => match ($scene) {
                    OverlayScene::StartingSoon => 'startingSoonSettingsAction',
                    OverlayScene::Voice => 'voiceSettingsAction',
                    OverlayScene::Chat => 'chatSettingsAction',
                    OverlayScene::Pub => 'pubSettingsAction',
                    OverlayScene::Coworking => null,
                },
                'size' => $scene === OverlayScene::Chat ? 'qualquer tamanho' : '1920 × 1080',
                'hasTransparentBackground' => $scene->hasTransparentBackground(),
                'previewWidth' => $scene === OverlayScene::Chat ? self::CHAT_PREVIEW_WIDTH : self::SCENE_WIDTH,
            ], OverlayScene::cases()),
        ];
    }

    /**
     * @param  list<ChatStyle|ChatDirection|ChatAlignment>  $cases
     * @return array<string, string>
     */
    private function labelsOf(array $cases): array
    {
        $labels = [];

        foreach ($cases as $case) {
            $labels[$case->value] = $case->getLabel();
        }

        return $labels;
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     */
    private function alertTypeOf(array $arguments): ?StreamEventType
    {
        $type = $arguments['type'] ?? null;

        return is_string($type) ? StreamEventType::tryFrom($type) : null;
    }

    /**
     * @param  array<array-key, mixed>  $arguments
     */
    private function sceneOf(array $arguments): ?OverlayScene
    {
        $scene = $arguments['scene'] ?? null;

        return is_string($scene) ? OverlayScene::tryFrom($scene) : null;
    }

    private function sceneSourcesLabel(?OverlayScene $scene): string
    {
        $chosenSourceIds = $scene instanceof OverlayScene
            ? $this->currentStreamer()->settings->sceneSources->allowedFor($scene, $this->overlaySourceIds())
            : null;

        if ($chosenSourceIds === null) {
            return 'Fontes: todas';
        }

        $handles = $this->overlaySources
            ->filter(fn (StreamerSource $source): bool => in_array($source->id, $chosenSourceIds, strict: true))
            ->map($this->sourceHandle(...))
            ->implode(', ');

        return 'Fontes: '.$handles;
    }

    /**
     * @return list<string>
     */
    private function overlaySourceIds(): array
    {
        return array_values($this->overlaySources->map(fn (StreamerSource $source): string => $source->id)->all());
    }

    private function sourceHandle(StreamerSource $source): string
    {
        $username = $source->identity->metadata['username'] ?? null;

        return '@'.(is_string($username) ? $username : $source->identity->external_account_id);
    }

    private function sceneUrl(OverlayScene $scene, string $token): string
    {
        return url(sprintf('/overlay/%s/%s', $token, $scene->value));
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
