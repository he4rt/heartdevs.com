<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Twitch\Actions;

use Filament\Actions\Action;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;

class ConnectTwitchAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $connectedIdentity = $this->resolveConnectedIdentity();
        $isConnected = $connectedIdentity instanceof ExternalIdentity;

        $this
            ->label($isConnected
                ? __('panel-admin::twitch.connect.reconnect', ['login' => $this->resolveLogin($connectedIdentity)])
                : __('panel-admin::twitch.connect.connect'))
            ->icon($isConnected ? 'heroicon-o-check-badge' : 'heroicon-o-link')
            ->color($isConnected ? 'gray' : 'primary')
            ->url(route('oauth.redirect', ['panel' => 'admin', 'provider' => 'twitch']));
    }

    public static function getDefaultName(): ?string
    {
        return 'connect-twitch';
    }

    private function resolveConnectedIdentity(): ?ExternalIdentity
    {
        $user = filament()->auth()->user();

        if (!$user instanceof User) {
            return null;
        }

        $identity = $user->providers()
            ->where('provider', IdentityProvider::Twitch)
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->first();

        return $identity instanceof ExternalIdentity ? $identity : null;
    }

    private function resolveLogin(ExternalIdentity $identity): string
    {
        $metadata = $identity->metadata ?? [];

        /** @var string $login */
        $login = $metadata['login']
            ?? $metadata['username']
            ?? $identity->external_account_id
            ?? 'twitch';

        return $login;
    }
}
