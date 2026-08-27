<?php

declare(strict_types=1);

namespace He4rt\Events\Enrollment\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum AttendanceRequirement: string implements HasColor, HasIcon, HasLabel
{
    use StringifyEnum;

    case AllDays = 'all_days';
    case AnyDay = 'any_day';
    case MinimumDays = 'minimum_days';

    public function getLabel(): string
    {
        return __('events::enums.attendance_requirement.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::AllDays => Color::Blue,
            self::AnyDay => Color::Green,
            self::MinimumDays => Color::Amber,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::AllDays => Heroicon::Fire,
            self::AnyDay => Heroicon::CheckCircle,
            self::MinimumDays => Heroicon::OutlinedChartBar,
        };
    }
}
