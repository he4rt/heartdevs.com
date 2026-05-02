<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum CaseStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Assigned = 'assigned';
    case Resolved = 'resolved';
    case Escalated = 'escalated';
    case Dismissed = 'dismissed';

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }

    public function getLabel(): string
    {
        return __('moderation::enums.case_status.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Pending => Color::Gray,
            self::Assigned => Color::Blue,
            self::Resolved => Color::Green,
            self::Escalated => Color::Red,
            self::Dismissed => Color::Amber,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::Assigned => Heroicon::UserCircle,
            self::Resolved => Heroicon::CheckCircle,
            self::Escalated => Heroicon::ExclamationCircle,
            self::Dismissed => Heroicon::XCircle,
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Assigned]);
    }
}
