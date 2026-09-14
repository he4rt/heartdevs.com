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
    case LinkedIn = 'linkedin';

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
            self::LinkedIn => Heroicon::Link
        };
    }

    public function getBrandIcon(): string
    {
        return match ($this) {
            self::Instagram => 'fab-instagram',
            self::Twitter => 'fab-x-twitter',
            self::Website => 'fas-globe',
            self::YouTube => 'fab-youtube',
            self::Bluesky => 'fab-bluesky',
            self::LinkedIn => 'fab-linkedin',
        };
    }

    public function getUrl(string $handle): string
    {
        if (str_starts_with($handle, 'http://') || str_starts_with($handle, 'https://')) {
            return $handle;
        }

        $slug = mb_ltrim($handle, '@');

        return match ($this) {
            self::Instagram => 'https://instagram.com/'.$slug,
            self::Twitter => 'https://x.com/'.$slug,
            self::LinkedIn => 'https://linkedin.com/in/'.$slug,
            self::YouTube => 'https://youtube.com/@'.$slug,
            self::Bluesky => 'https://bsky.app/profile/'.$slug,
            self::Website => 'https://'.$slug,
        };
    }
}
