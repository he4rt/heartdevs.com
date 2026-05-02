<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum Platform: string
{
    case Discord = 'discord';
    case Twitch = 'twitch';
    case GitHub = 'github';
    case Twitter = 'twitter';
    case Web = 'web';

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }
}
