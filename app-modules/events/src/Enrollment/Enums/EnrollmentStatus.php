<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum EnrollmentStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Waitlisted = 'waitlisted';
    case CheckedIn = 'checked_in';
    case Attended = 'attended';
    case Cancelled = 'cancelled';
    case Rejected = 'rejected';
    case NoShow = 'no_show';

    public function getLabel(): string
    {
        return __('events::enums.enrollment_status.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Pending => Color::Gray,
            self::Confirmed => Color::Blue,
            self::Waitlisted => Color::Amber,
            self::CheckedIn => Color::Teal,
            self::Attended => Color::Green,
            self::Cancelled => Color::Gray,
            self::Rejected => Color::Red,
            self::NoShow => Color::Orange,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::Confirmed => Heroicon::CheckCircle,
            self::Waitlisted => Heroicon::OutlinedQueueList,
            self::CheckedIn => Heroicon::Flag,
            self::Attended => Heroicon::Bolt,
            self::Cancelled => Heroicon::XCircle,
            self::Rejected => Heroicon::NoSymbol,
            self::NoShow => Heroicon::ExclamationCircle,
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Pending => in_array($target, [self::Confirmed, self::Rejected, self::Cancelled], strict: true),
            self::Waitlisted => in_array($target, [self::Confirmed, self::Cancelled], strict: true),
            self::Confirmed => in_array($target, [self::CheckedIn, self::Cancelled, self::NoShow], strict: true),
            self::CheckedIn => $target === self::Attended,
            self::Attended, self::Cancelled, self::Rejected, self::NoShow => false,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Attended, self::Cancelled, self::Rejected, self::NoShow], strict: true);
    }
}
