<?php

declare(strict_types=1);

namespace He4rt\Profile\Enums;

use App\Enums\Concerns\StringifyEnum;
use Filament\Support\Colors\Color;
use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

enum SkillProficiency: string implements HasColor, HasIcon, HasLabel
{
    use StringifyEnum;

    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
    case Expert = 'expert';

    public function getLabel(): string
    {
        return __('profile::enums.skill_proficiency.'.$this->value);
    }

    public function getColor(): array
    {
        return match ($this) {
            self::Beginner => Color::Gray,
            self::Intermediate => Color::Blue,
            self::Advanced => Color::Purple,
            self::Expert => Color::Amber,
        };
    }

    public function getIcon(): Heroicon
    {
        return match ($this) {
            self::Beginner => Heroicon::AcademicCap,
            self::Intermediate => Heroicon::ChartBar,
            self::Advanced => Heroicon::Star,
            self::Expert => Heroicon::Trophy,
        };
    }
}
