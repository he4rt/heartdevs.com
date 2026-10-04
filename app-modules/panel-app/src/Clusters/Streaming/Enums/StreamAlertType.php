<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming\Enums;

use Filament\Support\Contracts\HasLabel;

enum StreamAlertType: string implements HasLabel
{
    case Follow = 'follow';
    case Sub = 'sub';
    case GiftSub = 'gift-sub';
    case Cheer = 'cheer';
    case Raid = 'raid';

    public function getLabel(): string
    {
        return match ($this) {
            self::Follow => 'Follow',
            self::Sub => 'Sub',
            self::GiftSub => 'Gift sub',
            self::Cheer => 'Bits',
            self::Raid => 'Raid',
        };
    }

    public function getEmoji(): string
    {
        return match ($this) {
            self::Follow => '⭐',
            self::Sub => '💜',
            self::GiftSub => '🎁',
            self::Cheer => '💎',
            self::Raid => '⚡',
        };
    }

    public function getAccent(): string
    {
        return match ($this) {
            self::Follow => '#c9a4ff',
            self::Sub, self::GiftSub => '#8b2fe8',
            self::Cheer, self::Raid => '#ffcb05',
        };
    }

    public function getAlertTitle(): string
    {
        return match ($this) {
            self::Follow => 'NOVO FOLLOW',
            self::Sub => 'NOVO SUB',
            self::GiftSub => 'GIFT SUB',
            self::Cheer => 'BITS',
            self::Raid => 'RAID',
        };
    }
}
