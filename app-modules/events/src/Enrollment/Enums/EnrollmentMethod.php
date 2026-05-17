<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum EnrollmentMethod: string implements HasColor, HasIcon, HasLabel
{
    case Rsvp = 'rsvp';
    case RsvpCheckin = 'rsvp_checkin';
    case Application = 'application';

    public function getLabel(): string
    {
        return __('events::enums.enrollment_method.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Rsvp => Color::Green,
            self::RsvpCheckin => Color::Blue,
            self::Application => Color::Amber,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Rsvp => Heroicon::CheckCircle,
            self::RsvpCheckin => Heroicon::Flag,
            self::Application => Heroicon::Eye,
        };
    }
}
