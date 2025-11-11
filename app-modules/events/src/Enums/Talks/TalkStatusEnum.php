<?php

declare(strict_types=1);

namespace He4rt\Events\Enums\Talks;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum TalkStatusEnum: string implements HasColor, HasIcon, HasLabel
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Done = 'done';

    public function getColor(): array
    {
        return match ($this) {
            self::Pending => Color::Yellow,
            self::Accepted => Color::Blue,
            self::Done => Color::Green,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Pending => Heroicon::Clock,
            self::Accepted => Heroicon::CircleStack,
            self::Done => Heroicon::ArrowUpCircle,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Accepted => 'Live',
            self::Done => 'Done',
        };
    }
}
