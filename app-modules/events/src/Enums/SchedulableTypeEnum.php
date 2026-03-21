<?php

declare(strict_types=1);

namespace He4rt\Events\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Icons\Heroicon;

enum SchedulableTypeEnum: string implements HasIcon
{
    case Submission = 'event_submission';

    case Segment = 'event_segment';

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::Submission => Heroicon::Microphone,
            self::Segment => Heroicon::Bars2,
        };
    }
}
