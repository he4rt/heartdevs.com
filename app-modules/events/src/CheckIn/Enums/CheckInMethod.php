<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum CheckInMethod: string implements HasColor, HasIcon, HasLabel
{
    case Manual = 'manual';
    case NumericCode = 'numeric_code';
    case QrCode = 'qr_code';

    public function getLabel(): string
    {
        return __('events::enums.check_in_method.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Manual => Color::Gray,
            self::NumericCode => Color::Blue,
            self::QrCode => Color::Purple,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Manual => Heroicon::HandRaised,
            self::NumericCode => Heroicon::InformationCircle,
            self::QrCode => Heroicon::QrCode,
        };
    }
}
