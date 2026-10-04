<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming\Pages;

use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\IntegrationTwitch\OAuth\TwitchScopes;
use He4rt\PanelApp\Clusters\Streaming\Enums\StreamAlertType;
use He4rt\PanelApp\Clusters\Streaming\StreamingCluster;
use He4rt\PanelApp\Clusters\Streaming\StreamingPreviewData;
use He4rt\PanelApp\Pages\ProfilePage;
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

    #[Computed]
    public function twitchConnection(): ?ExternalIdentity
    {
        return $this->streamer()
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
        return $this->twitchConnection?->missingScopes(TwitchScopes::requestedFor('app', $this->streamer())) ?? [];
    }

    public function sendTestAlert(string $type): void
    {
        $alertType = StreamAlertType::tryFrom($type);

        if ($alertType === null) {
            return;
        }

        Notification::make()
            ->title(sprintf('Alerta de %s enviado', $alertType->getLabel()))
            ->body('Na versão final ele aparece na sua overlay.')
            ->success()
            ->send();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'alertTypes' => StreamAlertType::cases(),
            'stats' => StreamingPreviewData::stats(),
            'recentActivity' => StreamingPreviewData::recentActivity(),
            'profileUrl' => ProfilePage::getUrl(),
        ];
    }

    private function streamer(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }
}
