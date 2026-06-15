<?php

declare(strict_types=1);

namespace He4rt\Profile\Enums;

use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum SeniorityLevel: string implements HasColor, HasIcon, HasLabel
{
    case Junior = 'junior';
    case Pleno = 'pleno';
    case Senior = 'senior';

    public function getLabel(): string
    {
        return __('profile::enums.seniority_level.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Junior => Color::Green,
            self::Pleno => Color::Blue,
            self::Senior => Color::Purple,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Junior => Heroicon::AcademicCap,
            self::Pleno => Heroicon::CodeBracket,
            self::Senior => Heroicon::Star,
        };
    }
}
