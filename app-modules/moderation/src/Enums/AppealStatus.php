<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum AppealStatus: string
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

    public function isResolved(): bool
    {
        return in_array($this, [self::Upheld, self::Overturned]);
    }
}
