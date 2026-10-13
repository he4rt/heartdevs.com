<?php

declare(strict_types=1);

namespace He4rt\IntegrationTwitch\OAuth;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use He4rt\Streaming\Enums\ChatReader;

enum TwitchStreamerFeature: string implements HasColor, HasDescription, HasLabel
{
    case Alerts = 'alerts';
    case ChatOwnAccount = 'chat_own_account';
    case ChatHe4rtBot = 'chat_he4rt_bot';

    /**
     * @param  array<array-key, mixed>  $values
     * @return array<int, self>
     */
    public static function fromValues(array $values): array
    {
        $features = array_map(
            fn (mixed $value): ?self => is_string($value) ? self::tryFrom($value) : null,
            $values,
        );

        return array_values(array_unique(array_filter($features), SORT_REGULAR));
    }

    /**
     * @param  array<int, string>  $grantedScopes
     */
    public static function chatReaderFrom(array $grantedScopes): ?ChatReader
    {
        $grants = fn (self $feature): bool => array_diff($feature->scopes(), $grantedScopes) === [];

        return match (true) {
            $grants(self::ChatOwnAccount) => ChatReader::OwnAccount,
            $grants(self::ChatHe4rtBot) => ChatReader::He4rtBot,
            default => null,
        };
    }

    /**
     * @return array<int, self>
     */
    public static function neededFor(?ChatReader $chatReader): array
    {
        return $chatReader instanceof ChatReader ? [self::Alerts, self::fromChatReader($chatReader)] : [self::Alerts];
    }

    public static function fromChatReader(ChatReader $chatReader): self
    {
        return match ($chatReader) {
            ChatReader::OwnAccount => self::ChatOwnAccount,
            ChatReader::He4rtBot => self::ChatHe4rtBot,
        };
    }

    /**
     * @return array<int, string>
     */
    public function scopes(): array
    {
        return match ($this) {
            self::Alerts => ['moderator:read:followers', 'channel:read:subscriptions', 'bits:read'],
            self::ChatOwnAccount => ['user:read:chat', 'user:bot'],
            self::ChatHe4rtBot => ['channel:bot'],
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Alerts => 'Alertas',
            self::ChatOwnAccount => 'Chat lido pela minha conta',
            self::ChatHe4rtBot => 'Chat lido pela he4rtdevs',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Alerts => 'Follows, subs, bits e raids do seu canal aparecem na overlay.',
            self::ChatOwnAccount => 'A sua conta lê o chat. Nenhuma outra conta entra no seu canal.',
            self::ChatHe4rtBot => 'A conta bot da He4rt lê o chat. A Twitch pede só a permissão de bot no canal.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Alerts => 'warning',
            self::ChatOwnAccount => 'primary',
            self::ChatHe4rtBot => 'info',
        };
    }
}
