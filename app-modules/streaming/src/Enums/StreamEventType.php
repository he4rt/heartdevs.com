<?php

declare(strict_types=1);

namespace He4rt\Streaming\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

enum StreamEventType: string implements HasColor, HasDescription, HasLabel
{
    case Follow = 'follow';
    case Sub = 'sub';
    case GiftSub = 'gift_sub';
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

    public function getDescription(): string
    {
        return match ($this) {
            self::Follow => 'Alguém começou a seguir o canal.',
            self::Sub => 'Alguém assinou o canal ou renovou a assinatura.',
            self::GiftSub => 'Alguém deu assinaturas de presente.',
            self::Cheer => 'Alguém mandou bits.',
            self::Raid => 'Outro canal trouxe a audiência para cá.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Follow => 'info',
            self::Sub, self::GiftSub => 'primary',
            self::Cheer, self::Raid => 'warning',
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
