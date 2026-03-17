<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Enums;

use App\Contracts\OAuthClientContract;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use He4rt\Identity\Auth\DTOs\OAuthStateDTO;
use He4rt\IntegrationDiscord\OAuth\DiscordOAuthClient;
use He4rt\IntegrationTwitch\OAuth\Contracts\TwitchOAuthService;
use Illuminate\Contracts\Support\Htmlable;

enum IdentityProvider: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Discord = 'discord';
    case Twitch = 'twitch';

    public function getClient(): OAuthClientContract
    {
        return match ($this) {
            self::Twitch => resolve(TwitchOAuthService::class),
            self::Discord => resolve(DiscordOAuthClient::class),
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Discord => Color::Blue,
            self::Twitch => Color::Purple,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Discord => 'fab-discord',
            self::Twitch => 'fab-twitch',
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
