<?php

declare(strict_types=1);

namespace He4rt\Moderation\Enums;

enum CaseStatus: string
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

    public function isOpen(): bool
    {
        return in_array($this, [self::Pending, self::Assigned]);
    }
}
