<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Clusters\Streaming\StreamingCluster;
use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Enums\VoiceLayout;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Actions\RegenerateOverlayToken;
use He4rt\Streaming\Streamer\Actions\UpdateStreamerSettings;
use He4rt\Streaming\Streamer\Data\AlertSettings;
use He4rt\Streaming\Streamer\Data\StartingSoonSettings;
use He4rt\Streaming\Streamer\Data\VoiceSettings;
use He4rt\Streaming\Streamer\Models\Streamer;
use Livewire\Attributes\Locked;

class StreamOverlaysPage extends Page
{
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
        return [
            'scenes' => array_map(fn (OverlayScene $scene): array => [
                'scene' => $scene,
                'url' => $this->sceneUrl($scene, $this->overlayToken),
                'maskedUrl' => $this->sceneUrl($scene, str_repeat('•', 12)),
                'settingsAction' => match ($scene) {
                    OverlayScene::StartingSoon => 'startingSoonSettingsAction',
                    OverlayScene::Voice => 'voiceSettingsAction',
                    OverlayScene::Coworking => null,
                },
            ], OverlayScene::cases()),
        ];
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
