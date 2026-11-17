<?php

declare(strict_types=1);

namespace He4rt\Identity\SocialAccounts;

use App\Contracts\Enums\HasLabel;
use App\Contracts\Enums\HasTone;
use App\Enums\Tone;

enum SocialProvider: string implements HasLabel, HasTone
{
    case GitHub = 'github';
    case Google = 'google';
    case Discord = 'discord';
    case Twitch = 'twitch';

    public function getLabel(): string
    {
        return match ($this) {
            self::GitHub => 'GitHub',
            self::Google => 'Google',
            self::Discord => 'Discord',
            self::Twitch => 'Twitch',
        };
    }

    public function getTone(): Tone
    {
        return match ($this) {
            self::GitHub => Tone::Neutral,
            self::Google => Tone::Info,
            self::Discord => Tone::Primary,
            self::Twitch => Tone::Primary,
        };
    }
}
