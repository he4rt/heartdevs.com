<?php

declare(strict_types=1);

namespace He4rt\Profile\Enums;

enum SocialPlatform: string
{
    case Instagram = 'instagram';
    case Twitter = 'twitter';
    case Website = 'website';
    case YouTube = 'youtube';
    case Bluesky = 'bluesky';

    public function label(): string
    {
        return match ($this) {
            self::Instagram => 'Instagram',
            self::Twitter => 'Twitter',
            self::Website => 'Website',
            self::YouTube => 'YouTube',
            self::Bluesky => 'Bluesky',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(
            fn (self $platform): string => $platform->value,
            self::cases(),
        );
    }
}
