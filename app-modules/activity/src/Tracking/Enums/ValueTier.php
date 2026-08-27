<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ValueTier: string implements HasColor, HasLabel
{
    use StringifyEnum;

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
            self::High => Color::Red,
            self::Medium => Color::Yellow,
            self::Low => Color::Gray,
        };
    }
}
