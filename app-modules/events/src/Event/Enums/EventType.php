<?php

declare(strict_types=1);

namespace He4rt\Events\Event\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum EventType: string implements HasColor, HasIcon, HasLabel
{
    case Meetup = 'meetup';
    case Workshop = 'workshop';
    case Conference = 'conference';

    public function getLabel(): string
    {
        return __('events::enums.event_type.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Meetup => Color::Blue,
            self::Workshop => Color::Amber,
            self::Conference => Color::Purple,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Meetup => Heroicon::UserGroup,
            self::Workshop => Heroicon::CodeBracket,
            self::Conference => Heroicon::Megaphone,
        };
    }
}
