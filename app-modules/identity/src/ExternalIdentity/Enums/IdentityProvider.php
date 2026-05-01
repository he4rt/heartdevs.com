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
use He4rt\IntegrationDevTo\OAuth\DevToOAuthClient;
use He4rt\IntegrationDiscord\OAuth\DiscordOAuthClient;
use He4rt\IntegrationTwitch\OAuth\Contracts\TwitchOAuthService;
use RuntimeException;

enum IdentityProvider: string implements HasColor, HasDescription, HasIcon, HasLabel
{
    case Discord = 'discord';
    case Twitch = 'twitch';
    case DevTo = 'devto';
    case GitHub = 'github';

    public function getClient(): OAuthClientContract
    {
        return match ($this) {
            self::Twitch => resolve(TwitchOAuthService::class),
            self::Discord => resolve(DiscordOAuthClient::class),
            self::DevTo => resolve(DevToOAuthClient::class),
            self::GitHub => throw new RuntimeException('GitHub OAuth client not implemented yet.'),
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Discord => Color::Blue,
            self::Twitch => Color::Purple,
            self::DevTo => Color::Gray,
            self::GitHub => Color::Gray,
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Discord => 'fab-discord',
            self::Twitch => 'fab-twitch',
            self::DevTo => 'fab-dev',
            self::GitHub => 'fab-github',
        };
    }

    public function getLabel(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Discord => 'Conecte sua conta do Discord para gameficações e eventos.',
            self::Twitch => 'Conecte sua conta do Twitch para gameficações e eventos.',
            self::DevTo => 'Conecte sua conta do Dev.to para rastrear artigos e contribuições.',
            self::GitHub => 'Conecte sua conta do GitHub para exibir seu perfil e contribuições.',
        };
    }

    /**
     * @return array<int, string>
     */
    public function getScopes(): array
    {
        $scopes = match ($this) {
            self::Discord => config('services.discord.scopes'),
            self::Twitch => config('services.twitch.scopes'),
            self::DevTo => config('services.devto.scopes'),
            self::GitHub => config('services.github.scopes'),
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

    public function getType(): IdentityType
    {
        return match ($this) {
            self::Discord, self::Twitch, self::DevTo, self::GitHub => IdentityType::External,
        };
    }
}
