<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum Severity: string
{
    case Low = 'low';
    case Medium = 'medium';
    case High = 'high';
    case Critical = 'critical';

    public static function random(): self
    {
        $cases = self::cases();

        return $cases[array_rand($cases)];
    }

    public function weight(): int
    {
        return match ($this) {
            self::Low => 1,
            self::Medium => 2,
            self::High => 3,
            self::Critical => 4,
        };
    }
}
