<?php

declare(strict_types=1);

namespace He4rt\Events\Event\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum EventStatus: string implements HasColor, HasIcon, HasLabel
{
    case Draft = 'draft';
    case Published = 'published';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public static function viewableByParticipant(): array
    {
        return [
            self::Published,
            self::Completed,
            self::Cancelled,
        ];
    }

    public function getLabel(): string
    {
        return __('events::enums.event_status.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Draft => Color::Gray,
            self::Published => Color::Green,
            self::Completed => Color::Blue,
            self::Cancelled => Color::Red,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Draft => Heroicon::PencilSquare,
            self::Published => Heroicon::GlobeAlt,
            self::Completed => Heroicon::CheckBadge,
            self::Cancelled => Heroicon::XCircle,
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => in_array($target, [self::Published, self::Cancelled], strict: true),
            self::Published => in_array($target, [self::Completed, self::Cancelled], strict: true),
            self::Completed, self::Cancelled => false,
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Completed, self::Cancelled], strict: true);
    }

    public function isViewableByParticipant(): bool
    {
        return match ($this) {
            self::Published, self::Completed, self::Cancelled => true,
            self::Draft => false,
        };
    }
}
