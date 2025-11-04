<?php

declare(strict_types=1);

namespace He4rt\Events\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum AttendingStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case Attending = 'attending';
    case NotAttending = 'not_attending';
    case Waitlist = 'waitlist';

    public function getColor(): array
    {
        return match ($this) {
            self::Attending => Color::Blue,
            self::NotAttending => Color::Red,
            self::Waitlist => Color::Gray,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Attending => Heroicon::Check,
            self::NotAttending => Heroicon::XMark,
            self::Waitlist => Heroicon::Clock,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Attending => 'Attending',
            self::NotAttending => 'Not Attending',
            self::Waitlist => 'Waitlist',
        };
    }
}
