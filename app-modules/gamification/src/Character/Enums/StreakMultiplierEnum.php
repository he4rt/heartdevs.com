<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Enums;

use Filament\Support\Contracts\HasLabel;

enum StreakMultiplierEnum: int implements HasLabel
{
    case None = 0;       // 1.0x
    case Bronze = 3;     // 1.1x (streak 3-4)
    case Silver = 5;      // 1.25x (streak 5-9)
    case Gold = 10;       // 1.5x (streak 10+)

    public static function fromStreak(int $streak): self
    {
        return match (true) {
            $streak >= 10 => self::Gold,
            $streak >= 5 => self::Silver,
            $streak >= 3 => self::Bronze,
            default => self::None,
        };
    }

    public function getMultiplier(): float
    {
        return match ($this) {
            self::None => 1.0,
            self::Bronze => 1.1,
            self::Silver => 1.25,
            self::Gold => 1.5,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::None => '1.0x',
            self::Bronze => '1.1x',
            self::Silver => '1.25x',
            self::Gold => '1.5x',
        };
    }
}
