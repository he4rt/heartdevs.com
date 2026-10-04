<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Clusters\Streaming\Enums\OverlayScene;
use He4rt\PanelApp\Clusters\Streaming\StreamingCluster;
use He4rt\PanelApp\Clusters\Streaming\StreamingPreviewData;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

class StreamOverlaysPage extends Page
{
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

    public function mount(): void
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        $this->overlayToken = StreamingPreviewData::overlayToken($user);
    }

    /**
     * @return Action[]
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('regenerateToken')
                ->label('Gerar novos links')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Gerar novos links?')
                ->modalDescription('Os links atuais param de funcionar. Você vai precisar colar os novos no OBS.')
                ->modalSubmitActionLabel('Gerar novos links')
                ->action(function (): void {
                    $this->overlayToken = Str::lower(Str::random(32));

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
            ], OverlayScene::cases()),
        ];
    }

    private function sceneUrl(OverlayScene $scene, string $token): string
    {
        return url(sprintf('/overlay/%s/%s', $token, $scene->value));
    }
}
