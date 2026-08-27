<?php

declare(strict_types=1);

namespace He4rt\Profile\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum StartAvailability: string implements HasColor, HasIcon, HasLabel
{
    use StringifyEnum;

    case Immediate = 'immediate';
    case OneWeek = '1_week';
    case TwoWeeks = '2_weeks';
    case ThreeWeeks = '3_weeks';
    case OneMonth = '1_month';
    case TwoMonths = '2_months';
    case Negotiable = 'negotiable';

    public function getLabel(): string
    {
        return __('profile::enums.start_availability.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Immediate => Color::Green,
            self::OneWeek, self::TwoWeeks => Color::Amber,
            self::ThreeWeeks, self::OneMonth, self::TwoMonths => Color::Red,
            self::Negotiable => Color::Gray,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Immediate => Heroicon::BoltSlash,
            self::OneWeek, self::TwoWeeks, self::ThreeWeeks => Heroicon::Clock,
            self::OneMonth, self::TwoMonths => Heroicon::CalendarDays,
            self::Negotiable => Heroicon::ChatBubbleBottomCenterText,
        };
    }
}
