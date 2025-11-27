<?php

declare(strict_types=1);

namespace He4rt\Events\Enums;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Icons\Heroicon;
use He4rt\Events\Models\EventSegment;
use He4rt\Events\Models\Talk;

enum SchedulableTypeEnum: string implements HasIcon
{
    case Talk = Talk::class;

    case Segment = EventSegment::class;

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::Talk => Heroicon::Microphone,
            self::Segment => Heroicon::Bars2,
        };
    }
}
