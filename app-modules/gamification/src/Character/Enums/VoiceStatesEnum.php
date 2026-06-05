<?php

declare(strict_types=1);

namespace He4rt\Gamification\Character\Enums;

enum VoiceStatesEnum: string
{
    case Disabled = 'disabled';
    case Muted = 'muted';
    case Unmuted = 'unmuted';

    public function getExperienceMultiplier(): int
    {
        return match ($this) {
            self::Disabled => 0,
            self::Muted => 1,
            self::Unmuted => 3,
        };
    }
}
