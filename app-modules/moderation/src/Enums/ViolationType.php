<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum ViolationType: string
{
    case Spam = 'spam';
    case Toxicity = 'toxicity';
    case Harassment = 'harassment';
    case Nsfw = 'nsfw';
    case Raid = 'raid';
    case Impersonation = 'impersonation';
    case Other = 'other';

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }
}
