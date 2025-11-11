<?php

declare(strict_types=1);

namespace He4rt\Events\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum EventTypeEnum: string implements HasColor, HasIcon, HasLabel
{
    case Meetup = 'meetup';
    case Workshop = 'workshop';

    public function getColor(): array
    {
        return match ($this) {
            self::Meetup => Color::Blue,
            self::Workshop => Color::Green,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Meetup => Heroicon::Wallet,
            self::Workshop => Heroicon::ShoppingBag,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Meetup => 'Meetup',
            self::Workshop => 'Workshop',
        };
    }
}
