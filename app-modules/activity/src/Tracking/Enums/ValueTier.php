<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ValueTier: string implements HasColor, HasLabel
{
    case High = 'high';
    case Medium = 'medium';
    case Low = 'low';

    public function getLabel(): string
    {
        return match ($this) {
            self::High => 'High',
            self::Medium => 'Medium',
            self::Low => 'Low',
        };
    }

    public function getColor(): array
    {
        return match ($this) {
            self::High => Color::Purple,
            self::Medium => Color::Blue,
            self::Low => Color::Gray,
        };
    }
}
