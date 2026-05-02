<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum AppealStatus: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Reviewing = 'reviewing';
    case Upheld = 'upheld';
    case Overturned = 'overturned';

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }

    public function getLabel(): string
    {
        return __('moderation::enums.appeal_status.'.$this->value);
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Pending => 'gray',
            self::Reviewing => 'warning',
            self::Upheld => 'danger',
            self::Overturned => 'success',
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::Reviewing => Heroicon::Eye,
            self::Upheld => Heroicon::HandRaised,
            self::Overturned => Heroicon::ArrowPath,
        };
    }

    public function isResolved(): bool
    {
        return in_array($this, [self::Upheld, self::Overturned]);
    }
}
