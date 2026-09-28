<?php

declare(strict_types=1);

namespace He4rt\BotDiscord\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;
use Throwable;

/**
 * Discord JSON error codes returned by the HTTP API.
 *
 * @see https://discord.com/developers/docs/topics/opcodes-and-status-codes#json-json-error-codes
 */
enum DiscordErrorCode: int implements HasColor, HasDescription, HasLabel
{
    case CannotSendMessagesToUser = 50_007;
    case NoMutualGuilds = 50_278;

    public static function fromThrowable(Throwable $throwable): ?self
    {
        return self::tryFrom($throwable->getCode());
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::CannotSendMessagesToUser => __('bot-discord::enums.discord_error_code.cannot_send_messages_to_user.label'),
            self::NoMutualGuilds => __('bot-discord::enums.discord_error_code.no_mutual_guilds.label'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::CannotSendMessagesToUser => 'warning',
            self::NoMutualGuilds => 'gray',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::CannotSendMessagesToUser => __('bot-discord::enums.discord_error_code.cannot_send_messages_to_user.description'),
            self::NoMutualGuilds => __('bot-discord::enums.discord_error_code.no_mutual_guilds.description'),
        };
    }
}
