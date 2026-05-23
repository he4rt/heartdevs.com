<?php

declare(strict_types=1);

namespace He4rt\Profile\Enums;

use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum SocialPlatform: string implements HasIcon, HasLabel
{
    case Instagram = 'instagram';
    case Twitter = 'twitter';
    case Website = 'website';
    case YouTube = 'youtube';
    case Bluesky = 'bluesky';

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

    public function getLabel(): string
    {
        return __('profile::enums.social_platform.'.$this->value);
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Instagram => Heroicon::Camera,
            self::Twitter => Heroicon::ChatBubbleLeft,
            self::Website => Heroicon::GlobeAlt,
            self::YouTube => Heroicon::PlayCircle,
            self::Bluesky => Heroicon::Cloud,
        };
    }
}
