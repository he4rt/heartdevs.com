<?php

declare(strict_types=1);

namespace He4rt\Authentication\Enums;

use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use He4rt\Authentication\Contracts\OAuthClientContract;
use He4rt\Authentication\DTO\OAuthStateDTO;
use He4rt\Integrations\Discord\OAuth\DiscordOAuthClient;
use He4rt\Integrations\Twitch\OAuth\Contracts\TwitchOAuthService;
use Illuminate\Contracts\Support\Htmlable;

enum OAuthProviderEnum: string implements HasDescription, HasIcon, HasLabel
{
    case Twitch = 'twitch';

    case Discord = 'discord';

    public function getClient(): OAuthClientContract
    {
        return match ($this) {
            self::Twitch => app(TwitchOAuthService::class),
            self::Discord => app(DiscordOAuthClient::class),
        };
    }

    public function getLabel(): string
    {
        return $this->name;
    }

    public function getDescription(): string|Htmlable|null
    {
        return match ($this) {
            self::Discord => 'Conecte sua conta do Discord para gameficações e eventos.',
            self::Twitch => 'Conecte sua conta do Twitch para gameficações e eventos.',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Discord => 'fab-discord',
            self::Twitch => 'fab-twitch',
        };
    }

    public function getScopes(): array
    {
        $scopes = match ($this) {
            self::Discord => config('services.discord.scopes'),
            self::Twitch => config('services.twitch.scopes'),
        };

        return explode(' ', $scopes);
    }

    public function isEnabled(): bool
    {
        return config(sprintf('services.%s.enabled', $this->value));
    }

    public function getRedirectUri(?string $tenant = null): string
    {
        return $this->getClient()->redirectUrl(
            new OAuthStateDTO(
                filament()->getCurrentPanel()->getId(),
                $tenant
            )
        );
    }
}
